<?php

namespace App\Http\Controllers\Api\Pos;

use App\Cashier;
use App\Http\Controllers\Base\TransactionControllerBase;
use App\Http\Controllers\Controller;
use App\Models\Good;
use App\Models\GoodUnit;
use App\Models\Member;
use App\Models\PosDevice;
use App\Models\PosSyncReject;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * API Kasir HP: menerima transaksi penjualan yang dibuat di HP (juga yang
 * dibuat saat offline) dan menyimpannya lewat storeTransactionBase(), fungsi
 * yang sama dengan kasir desktop. Lihat docs/rancangan-kasir-hp-tahap1.md §5.
 *
 * Hasil per transaksi:
 *   saved     : tersimpan sekarang
 *   duplicate : client_uuid sudah pernah tersimpan (kiriman ulang) -> aman;
 *               cancelled = true bila admin sudah menghapus/membatalkannya di web
 *   rejected  : data tidak bisa diterima; dicatat di pos_sync_rejects untuk admin
 *   error     : gangguan server; HP boleh mengirim ulang nanti
 */
class TransactionSyncController extends Controller
{
    use TransactionControllerBase;
    use ReadsSince;

    public const MAX_PER_REQUEST = 20;
    public const MAX_AGE_DAYS    = 7;
    public const NON_MEMBER_ID   = 1; // member "Non Member"

    /** Jenis transaksi setelah admin menghapus ("deleted") atau membatalkan ("not valid") di web. */
    public const CANCELLED_TYPES = ['deleted', 'not valid'];
    /** Tarikan penuh daftar batal: HP sendiri membuang transaksi terkirim yang lebih lama dari 60 hari. */
    public const CANCELLED_FULL_DAYS = 62;
    /**
     * Daftar ubah hanya untuk transaksi yang dijual hari ini dan kemarin: riwayat HP hanya
     * menampilkan transaksi hari ini, dan isi transaksi ikut dikirim (supaya tidak terlalu besar).
     */
    public const CHANGED_DAYS = 2;

    /**
     * Transaksi Kasir HP/PC yang sudah dihapus atau dibatalkan admin di web, supaya
     * HP/PC ikut menghapusnya dari riwayat. HP hanya menghapus client_uuid yang ada
     * di HP itu, jadi daftar tidak dibatasi per perangkat (tetap benar walau HP
     * pernah dipasang ulang sebagai perangkat lain).
     *
     * Tanpa ?since= : semua dari 62 hari terakhir. Dengan ?since= : yang berubah sejak itu.
     */
    public function cancelled(Request $request)
    {
        [$since, $serverTime] = $this->since($request);

        $rows = DB::table('transactions')
            ->whereNotNull('client_uuid')
            ->where(fn ($q) => $q->whereIn('type', self::CANCELLED_TYPES)->orWhereNotNull('deleted_at'));

        if ($since) {
            $rows->where(fn ($q) => $q->where('updated_at', '>', $since)->orWhere('deleted_at', '>', $since));
        } else {
            $rows->where('created_at', '>=', now()->subDays(self::CANCELLED_FULL_DAYS));
        }

        return response()->json([
            'server_time' => $serverTime,
            'full'        => $since === null,
            'cancelled'   => $rows->orderBy('id')->get(['id', 'client_uuid', 'receipt_no', 'type', 'deleted_at'])->map(fn ($t) => [
                'client_uuid'    => $t->client_uuid,
                'transaction_id' => (int) $t->id,
                'receipt_no'     => $t->receipt_no,
                'status'         => $t->deleted_at !== null ? 'deleted' : $t->type,
            ])->values(),
        ]);
    }

    /**
     * Isi terbaru transaksi Kasir HP/PC yang berubah di server, supaya riwayat di HP/PC
     * ikut berubah bila admin mengubah transaksi di web (jumlah barang, member, catatan,
     * uang bayar). Transaksi yang dihapus/dibatalkan tidak di sini (lihat cancelled()).
     *
     * Yang baru masuk dari HP juga ikut terkirim (updated_at-nya baru); HP membandingkan
     * dengan isinya sendiri dan hanya memperbarui yang benar-benar berbeda. Tidak dibatasi
     * per perangkat, dengan alasan yang sama seperti cancelled().
     *
     * Hanya transaksi yang dijual hari ini dan kemarin (CHANGED_DAYS). Tanpa ?since= : semuanya;
     * dengan ?since= : yang transaksi atau baris barangnya berubah sejak itu.
     */
    public function changed(Request $request)
    {
        [$since, $serverTime] = $this->since($request);

        $rows = DB::table('transactions')
            ->whereNotNull('client_uuid')
            ->whereNull('deleted_at')
            ->where(fn ($q) => $q->whereNull('type')->orWhereNotIn('type', self::CANCELLED_TYPES))
            ->where('created_at', '>=', now()->startOfDay()->subDays(self::CHANGED_DAYS - 1));

        if ($since) {
            // Admin mengubah jumlah barang: baris barangnya berubah walau angka transaksinya kebetulan sama.
            $rows->where(fn ($q) => $q->where('updated_at', '>', $since)->orWhereExists(
                fn ($d) => $d->selectRaw('1')->from('transaction_details')
                             ->whereColumn('transaction_details.transaction_id', 'transactions.id')
                             ->where('transaction_details.updated_at', '>', $since)
            ));
        }

        $transactions = $rows->orderBy('id')->get([
            'id', 'client_uuid', 'receipt_no', 'member_id', 'payment', 'total_item_price', 'total_discount_price',
            'total_sum_price', 'money_paid', 'money_returned', 'note',
        ]);
        $ids = $transactions->pluck('id')->all();

        $details = DB::table('transaction_details')
            ->leftJoin('good_units', 'good_units.id', '=', 'transaction_details.good_unit_id')
            ->leftJoin('goods', 'goods.id', '=', 'good_units.good_id')
            ->leftJoin('types', 'types.id', '=', 'goods.type_id')
            ->leftJoin('units', 'units.id', '=', 'good_units.unit_id')
            ->whereIntegerInRaw('transaction_details.transaction_id', $ids)
            ->whereNull('transaction_details.deleted_at')
            ->orderBy('transaction_details.id')
            ->get([
                'transaction_details.transaction_id', 'transaction_details.good_unit_id', 'transaction_details.quantity',
                'transaction_details.selling_price', 'transaction_details.discount_price', 'transaction_details.sum_price',
                'good_units.good_id', 'goods.code', 'goods.name', 'types.name as type_name', 'units.code as unit_code', 'units.name as unit_name',
            ])
            ->groupBy('transaction_id');

        $memberIds = $transactions->pluck('member_id')->filter()->unique()->values()->all();
        $members   = DB::table('members')->whereIntegerInRaw('id', $memberIds)->pluck('name', 'id');

        $num = fn ($v) => round((float) $v, 2);

        return response()->json([
            'server_time' => $serverTime,
            'full'        => $since === null,
            'changed'     => $transactions->map(function ($t) use ($details, $members, $num) {
                // "Non Member" = tanpa member, sama seperti di HP.
                $memberId = $t->member_id && (int) $t->member_id !== self::NON_MEMBER_ID ? (int) $t->member_id : null;

                return [
                    'client_uuid'    => $t->client_uuid,
                    'transaction_id' => (int) $t->id,
                    'receipt_no'     => $t->receipt_no,
                    'member_id'      => $memberId,
                    'member_name'    => $memberId ? ($members[$memberId] ?? null) : null,
                    'payment'        => $t->payment,
                    'total_item'     => $num($t->total_item_price),
                    'total_discount' => $num($t->total_discount_price),
                    'total'          => $num($t->total_sum_price),
                    'money_paid'     => $num($t->money_paid),
                    'money_returned' => $num($t->money_returned),
                    'note'           => $t->note,
                    'items'          => ($details[$t->id] ?? collect())->map(fn ($d) => [
                        'good_id'      => $d->good_id !== null ? (int) $d->good_id : null,
                        'good_unit_id' => (int) $d->good_unit_id,
                        'code'         => $d->code,
                        'name'         => $d->good_id !== null ? Good::formatName($d->type_name, $d->name) : $d->name,
                        'unit'         => $d->unit_code ?: $d->unit_name,
                        'quantity'     => $num($d->quantity),
                        'price'        => $num($d->selling_price),
                        'discount'     => $num($d->discount_price),
                        'sum'          => $num($d->sum_price),
                    ])->values(),
                ];
            })->values(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['transactions' => 'required|array|min:1|max:' . self::MAX_PER_REQUEST]);

        /** @var PosDevice $device */
        $device = $request->attributes->get('pos_device');
        $list   = array_values($request->input('transactions'));

        // Simpan berurutan sesuai waktu jual; jawaban tetap urut seperti kiriman.
        $order = array_keys($list);
        usort($order, fn ($a, $b) => strcmp((string) ($list[$a]['sold_at'] ?? ''), (string) ($list[$b]['sold_at'] ?? '')) ?: $a <=> $b);

        $results = [];
        foreach ($order as $i) {
            $results[$i] = $this->process($device, $list[$i]);
        }
        ksort($results);

        $device->forceFill(['last_sync_at' => now()])->saveQuietly();

        return response()->json(['results' => array_values($results)]);
    }

    private function process(PosDevice $device, $sale): array
    {
        $uuid = is_array($sale) ? ($sale['client_uuid'] ?? null) : null;

        if (! is_string($uuid) || ! Str::isUuid($uuid)) {
            return $this->reject($device, $sale, 'client_uuid kosong atau bukan UUID.');
        }

        if ($saved = $this->saved($uuid)) {
            return $this->duplicate($uuid, $saved);
        }

        // Sudah pernah ditolak dan belum dibereskan admin: jangan dicatat dua kali.
        $previous = PosSyncReject::where('client_uuid', $uuid)->whereNull('resolved_at')->first();
        if ($previous) {
            return ['client_uuid' => $uuid, 'status' => 'rejected', 'message' => $previous->reason];
        }

        try {
            [$clean, $soldAt] = $this->validateSale($sale);
        } catch (ValidationException $e) {
            return $this->reject($device, $sale, $this->messages($e));
        }

        try {
            $transaction = DB::transaction(function () use ($device, $clean, $soldAt, $uuid) {
                // Tidak ada akun yang login di API: stok yang jadi minus dicatat atas nama kasirnya.
                $transaction = StockService::denganSebab(
                    ['aksi' => 'kasir_hp', 'role' => 'cashier', 'role_id' => (int) $clean['cashier_id']],
                    fn () => $this->storeTransactionBase('cashier', $clean['cashier_id'], $this->desktopRequest($clean), $soldAt)
                );

                $transaction->forceFill([
                    'client_uuid'   => $uuid,
                    'pos_device_id' => $device->id,
                    'receipt_no'    => $clean['receipt_no'],
                    'sold_at'       => $soldAt,
                ])->saveQuietly();

                return $transaction;
            });
        } catch (UniqueConstraintViolationException $e) {
            // Kiriman yang sama sedang/baru saja diproses request lain.
            if ($saved = $this->saved($uuid)) {
                return $this->duplicate($uuid, $saved);
            }
            report($e);

            return ['client_uuid' => $uuid, 'status' => 'error', 'message' => 'Gangguan server, coba kirim ulang nanti.'];
        } catch (ValidationException $e) {
            // Mis. tanggal jual masuk periode yang sudah tutup buku.
            return $this->reject($device, $sale, $this->messages($e));
        } catch (\Throwable $e) {
            report($e);

            return ['client_uuid' => $uuid, 'status' => 'error', 'message' => 'Gangguan server, coba kirim ulang nanti.'];
        }

        return ['client_uuid' => $uuid, 'status' => 'saved', 'transaction_id' => $transaction->id];
    }

    private function saved(string $uuid): ?object
    {
        // Termasuk transaksi yang sudah dihapus admin: kiriman ulang tidak boleh memunculkannya lagi.
        return DB::table('transactions')->where('client_uuid', $uuid)->first(['id', 'type', 'deleted_at']);
    }

    private function duplicate(string $uuid, object $saved): array
    {
        return [
            'client_uuid'    => $uuid,
            'status'         => 'duplicate',
            'transaction_id' => (int) $saved->id,
            'cancelled'      => $saved->deleted_at !== null || in_array($saved->type, self::CANCELLED_TYPES, true),
        ];
    }

    /**
     * Periksa & hitung ulang semua angka. Mengembalikan [data bersih, waktu jual].
     *
     * @throws ValidationException
     */
    private function validateSale(array $sale): array
    {
        $errors = [];
        $fail   = function (string $msg) use (&$errors) { $errors[] = $msg; };

        // Kasir
        $cashier = isset($sale['cashier_id']) && is_numeric($sale['cashier_id']) ? Cashier::find((int) $sale['cashier_id']) : null;
        if ($cashier === null) {
            $fail('Kasir tidak ditemukan.');
        } elseif (! $cashier->is_active) {
            $fail('Akun kasir ' . $cashier->name . ' tidak aktif.');
        }

        // Member: hanya yang sudah ada (HP tidak bisa menambah member).
        $memberId = $sale['member_id'] ?? null;
        if ($memberId !== null && $memberId !== '') {
            if (! is_numeric($memberId) || Member::find((int) $memberId) === null) {
                $fail('Member (ID ' . $memberId . ') tidak ditemukan.');
            }
            $memberId = (int) $memberId;
        } else {
            $memberId = self::NON_MEMBER_ID; // sama dengan bawaan form kasir desktop
        }

        // Pembayaran
        $payment = $sale['payment'] ?? null;
        if (! in_array($payment, ['cash', 'transfer'], true)) {
            $fail('Metode pembayaran harus cash atau transfer.');
        }

        // Waktu jual
        $soldAt = null;
        try {
            $soldAt = isset($sale['sold_at']) && is_string($sale['sold_at'])
                ? Carbon::parse($sale['sold_at'])->setTimezone(config('app.timezone'))
                : null;
        } catch (\Throwable $e) {
            $soldAt = null;
        }
        if ($soldAt === null) {
            $fail('Waktu transaksi (sold_at) kosong atau tidak terbaca.');
        } elseif ($soldAt->gt(now()->addMinutes(10))) {
            $fail('Waktu transaksi di masa depan (' . $soldAt->format('d-m-Y H:i') . '). Cek jam HP.');
        } elseif ($soldAt->lt(now()->subDays(self::MAX_AGE_DAYS))) {
            $fail('Transaksi lebih lama dari ' . self::MAX_AGE_DAYS . ' hari (' . $soldAt->format('d-m-Y H:i') . ').');
        }

        // Barang
        $items = $sale['items'] ?? null;
        $lines = [];
        $gross = 0;
        $sum   = 0;
        if (! is_array($items) || count($items) === 0) {
            $fail('Transaksi tidak berisi barang.');
        } elseif (count($items) > 200) {
            $fail('Terlalu banyak baris barang (maks. 200).');
        } else {
            foreach (array_values($items) as $n => $item) {
                $row      = 'Baris ' . ($n + 1);
                $unitId   = $item['good_unit_id'] ?? null;
                $qty      = $item['quantity'] ?? null;
                $price    = $item['price'] ?? null;
                $discount = $item['discount'] ?? 0;

                $goodUnit = is_numeric($unitId) ? GoodUnit::find((int) $unitId) : null;
                if ($goodUnit === null || $goodUnit->good === null || $goodUnit->unit === null) {
                    $fail($row . ': satuan barang tidak ditemukan (ID ' . (is_scalar($unitId) ? $unitId : '?') . ').');
                    continue;
                }
                $name = $goodUnit->good->getFullName();
                if (! is_numeric($qty) || $qty <= 0) {
                    $fail($row . ' ' . $name . ': jumlah harus lebih dari 0.');
                    continue;
                }
                if (! is_numeric($price) || $price < 0) {
                    $fail($row . ' ' . $name . ': harga tidak valid.');
                    continue;
                }
                if (! is_numeric($discount) || $discount < 0) {
                    $fail($row . ' ' . $name . ': potongan tidak valid.');
                    continue;
                }

                $line   = round($price * $qty, 2);
                $lines[] = [
                    'good_unit_id' => $goodUnit->id,
                    'quantity'     => round((float) $qty, 2),
                    'price'        => (float) $price,
                    'discount'     => (float) $discount,
                    'sum'          => round($line - $discount, 2),
                ];
                $gross += $line;
                $sum   += $line - $discount;
            }
        }

        // Total
        $totalDiscount = $sale['total_discount'] ?? 0;
        if (! is_numeric($totalDiscount) || $totalDiscount < 0) {
            $fail('Potongan harga akhir tidak valid.');
            $totalDiscount = 0;
        }
        $total = round($sum - $totalDiscount, 2);

        if (! isset($sale['total']) || ! is_numeric($sale['total']) || abs($sale['total'] - $total) > 1) {
            $fail('Total di HP (' . (is_scalar($sale['total'] ?? null) ? $sale['total'] : '-') . ') tidak sama dengan hitungan server (' . $total . ').');
        }
        if ($total < 0) {
            $fail('Total transaksi minus.');
        }

        $paid = $sale['money_paid'] ?? null;
        if (! is_numeric($paid)) {
            $fail('Uang bayar kosong.');
        } elseif ($paid < $total) {
            $fail('Uang bayar (' . $paid . ') kurang dari total (' . $total . '). Kasir HP tidak boleh membuat piutang.');
        }

        $receipt = $sale['receipt_no'] ?? null;
        if ($receipt !== null && (! is_string($receipt) || mb_strlen($receipt) > 30)) {
            $fail('Nomor nota maksimal 30 karakter.');
        }

        $note = $sale['note'] ?? null;
        if ($note !== null && ! is_string($note)) {
            $fail('Catatan tidak valid.');
        }

        if ($errors) {
            throw ValidationException::withMessages(['transaction' => $errors]);
        }

        return [[
            'cashier_id'     => $cashier->id,
            'member_id'      => $memberId,
            'payment'        => $payment,
            'lines'          => $lines,
            'gross'          => round($gross, 2),
            'total_discount' => (float) $totalDiscount,
            'total'          => $total,
            'money_paid'     => (float) $paid,
            'money_returned' => round($paid - $total, 2),
            'receipt_no'     => $receipt,
            'note'           => $note !== null ? mb_substr($note, 0, 255) : null,
        ], $soldAt];
    }

    /** Bentuk data seperti form kasir desktop, supaya storeTransactionBase() dipakai apa adanya. */
    private function desktopRequest(array $clean): Request
    {
        // Selalu dengan titik desimal & 2 angka ("12500.00"): parseNumber() membacanya
        // sebagai desimal, bukan pemisah ribuan.
        $n = fn ($v) => number_format((float) $v, 2, '.', '');

        $data = [
            'type'                 => 'normal',
            'payment'              => $clean['payment'],
            'member_id'            => $clean['member_id'],
            'total_item_price'     => $n($clean['gross']),
            'total_discount_price' => $n($clean['total_discount']),
            'total_sum_price'      => $n($clean['total']),
            'voucher'              => null,
            'voucher_nominal'      => 0,
            'money_paid'           => $n($clean['money_paid']),
            'money_returned'       => $n($clean['money_returned']),
            'note'                 => $clean['note'],
            'is_promo'             => 0,
            'barcodes'             => [],
            'quantities'           => [],
            'prices'               => [],
            'discounts'            => [],
            'sums'                 => [],
        ];

        foreach ($clean['lines'] as $line) {
            $data['barcodes'][]   = $line['good_unit_id'];
            $data['quantities'][] = $line['quantity'];
            $data['prices'][]     = $n($line['price']);
            $data['discounts'][]  = $n($line['discount']);
            $data['sums'][]       = $n($line['sum']);
        }

        return Request::create('/api/pos/v1/transactions', 'POST', $data);
    }

    private function reject(PosDevice $device, $sale, string $reason): array
    {
        $uuid = is_array($sale) && is_string($sale['client_uuid'] ?? null) ? mb_substr($sale['client_uuid'], 0, 36) : null;

        PosSyncReject::create([
            'pos_device_id' => $device->id,
            'client_uuid'   => $uuid,
            'receipt_no'    => is_array($sale) && is_string($sale['receipt_no'] ?? null) ? mb_substr($sale['receipt_no'], 0, 30) : null,
            'payload'       => json_encode($sale, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
            'reason'        => $reason,
        ]);

        return ['client_uuid' => $uuid, 'status' => 'rejected', 'message' => $reason];
    }

    private function messages(ValidationException $e): string
    {
        return implode(' ', array_merge(...array_values($e->errors())));
    }
}
