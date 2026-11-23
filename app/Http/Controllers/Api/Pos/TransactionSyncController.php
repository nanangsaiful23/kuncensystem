<?php

namespace App\Http\Controllers\Api\Pos;

use App\Cashier;
use App\Http\Controllers\Base\TransactionControllerBase;
use App\Http\Controllers\Controller;
use App\Models\GoodUnit;
use App\Models\Member;
use App\Models\PosDevice;
use App\Models\PosSyncReject;
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
 *   duplicate : client_uuid sudah pernah tersimpan (kiriman ulang) -> aman
 *   rejected  : data tidak bisa diterima; dicatat di pos_sync_rejects untuk admin
 *   error     : gangguan server; HP boleh mengirim ulang nanti
 */
class TransactionSyncController extends Controller
{
    use TransactionControllerBase;

    public const MAX_PER_REQUEST = 20;
    public const MAX_AGE_DAYS    = 7;
    public const NON_MEMBER_ID   = 1; // member "Non Member"

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

        if ($id = $this->savedId($uuid)) {
            return ['client_uuid' => $uuid, 'status' => 'duplicate', 'transaction_id' => $id];
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
                $transaction = $this->storeTransactionBase('cashier', $clean['cashier_id'], $this->desktopRequest($clean), $soldAt);

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
            if ($id = $this->savedId($uuid)) {
                return ['client_uuid' => $uuid, 'status' => 'duplicate', 'transaction_id' => $id];
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

    private function savedId(string $uuid): ?int
    {
        // Termasuk transaksi yang sudah dihapus admin: kiriman ulang tidak boleh memunculkannya lagi.
        $id = DB::table('transactions')->where('client_uuid', $uuid)->value('id');

        return $id ? (int) $id : null;
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
                $name = $goodUnit->good->name;
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
