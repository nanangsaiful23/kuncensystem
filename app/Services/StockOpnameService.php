<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Distributor;
use App\Models\GoodLoading;
use App\Models\GoodLoadingDetail;
use App\Models\GoodUnit;
use App\Models\Journal;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Stock opname: stok sistem dikoreksi ke hasil hitung fisik.
 *
 * Tiap barang yang selisih tetap mendapat satu transaksi (kurang) atau satu
 * loading (lebih), karena kartu stok, rugi per distributor dan rincian
 * "Hilang / selisih opname" membaca baris-baris itu.
 *
 * Jurnalnya TIDAK per barang. Satu SO paling banyak dua jurnal, keduanya
 * type 'stock_opname' dengan type_id = id SO:
 *
 *   Rugi   D 5215 Biaya Penyusutan Barang / K 1141 Persediaan   total barang kurang
 *   Lebih  D 1141 Persediaan / K 5215 Biaya Penyusutan Barang   total barang lebih
 *
 * Kurang dan lebih sengaja tidak dihitung bersih: kelebihan satu barang tidak
 * boleh menutupi kehilangan barang lain. Keterangan jurnal memuat barang
 * dengan selisih terbesar; rinciannya ada di halaman detail SO dan laporan
 * Barang Sering Hilang.
 *
 * Karena jurnalnya gabungan, transaksi & loading hasil SO tidak bisa diedit
 * atau dihapus satu per satu. Koreksinya lewat stock opname baru.
 */
class StockOpnameService
{
    const JURNAL = 'stock_opname';

    /** Panjang kolom journals.name. */
    const PANJANG_KETERANGAN = 255;

    const DISTRIBUTOR = 'Stock Opname';

    const PESAN_TERKUNCI = ':apa ini dibuat oleh Stock Opname. Jurnalnya digabung per stock opname, '
        . 'jadi tidak bisa diedit atau dihapus satu per satu. Koreksi stoknya lewat Stock Opname baru.';

    /**
     * Baris dari form stock opname (names = id barang, units = id satuan,
     * new_stocks = hasil hitung fisik dalam satuan itu). old_stocks dari form
     * tidak dipakai: stok sebelum dihitung diambil dari server saat disimpan.
     *
     * @return array<int, array{good_id: mixed, unit_id: mixed, new_stock: mixed}>
     */
    public static function barisDariRequest(Request $request): array
    {
        $baris = [];

        foreach ((array) $request->names as $i => $goodId) {
            if ($goodId === null || $goodId === '') {
                continue;
            }

            $baris[] = [
                'good_id'   => $goodId,
                'unit_id'   => $request->units[$i] ?? null,
                'new_stock' => $request->new_stocks[$i] ?? 0,
            ];
        }

        return $baris;
    }

    /**
     * Simpan satu stock opname beserta koreksi stok dan jurnalnya.
     *
     * @param  array<int, array{good_id: mixed, unit_id: mixed, new_stock: mixed}>  $baris
     */
    public static function simpan(string $role, $roleId, ?string $note, ?string $checker, array $baris): StockOpname
    {
        return StockService::denganSebab(
            ['aksi' => 'stock_opname', 'role' => $role, 'role_id' => $roleId],
            fn () => DB::transaction(fn () => self::simpanDalamTransaksi($role, $roleId, $note, $checker, $baris))
        );
    }

    private static function simpanDalamTransaksi(string $role, $roleId, ?string $note, ?string $checker, array $baris): StockOpname
    {
        $opname = StockOpname::create([
            'role' => $role, 'role_id' => $roleId, 'note' => $note, 'checker' => $checker,
        ]);

        $kurang = [];
        $lebih  = [];

        foreach ($baris as $row) {
            $goodUnit = GoodUnit::with('unit')
                ->where('good_id', $row['good_id'])
                ->where('unit_id', $row['unit_id'])
                ->first();

            if ($goodUnit === null || $goodUnit->unit === null || $goodUnit->good === null) {
                continue;
            }

            $good = $goodUnit->good;

            // Stok sebelum dihitung diambil dari server saat menyimpan, dalam
            // satuan yang dihitung (penjualan bisa terjadi sejak form dibuka).
            $lama = round(StockService::stock($good) * StockService::smallestQuantity($good)
                / max((float) $goodUnit->unit->quantity, 0.000001), 3);
            $baru    = round((float) $row['new_stock'], 3);
            $selisih = round($baru - $lama, 3);
            $nilai   = round(abs($selisih) * (float) $goodUnit->buy_price, 2);

            if ($selisih > 0) {
                self::loadingLebih($role, $roleId, $note, $checker, $goodUnit, $lama, $selisih, $nilai);
                $good->update(['last_loading' => date('Y-m-d')]);
            } elseif ($selisih < 0) {
                self::transaksiKurang($role, $roleId, $note, $goodUnit, $lama, -$selisih, $nilai);
                $good->update(['last_transaction' => date('Y-m-d')]);
            }

            StockOpnameDetail::create([
                'stock_opname_id' => $opname->id,
                'good_unit_id'    => $goodUnit->id,
                'old_stock'       => $lama,
                'new_stock'       => $baru,
                'total'           => $nilai,
            ]);

            if ($selisih != 0) {
                $item = [
                    'nama'    => $good->getFullName(),
                    'satuan'  => $goodUnit->unit->name,
                    'selisih' => $selisih,
                    'nilai'   => $nilai,
                ];
                if ($selisih > 0) {
                    $lebih[] = $item;
                } else {
                    $kurang[] = $item;
                }
            }
        }

        self::buatJurnal($opname, 'kurang', $kurang);
        self::buatJurnal($opname, 'lebih', $lebih);

        $opname->update(['total' => round(array_sum(array_column($lebih, 'nilai')) - array_sum(array_column($kurang, 'nilai')), 2)]);

        return $opname;
    }

    private static function loadingLebih(string $role, $roleId, ?string $note, ?string $checker, GoodUnit $goodUnit, float $lama, float $qty, float $nilai): void
    {
        $distributor = Distributor::where('name', self::DISTRIBUTOR)->first()
            ?? Distributor::create(['name' => self::DISTRIBUTOR]);

        $loading = GoodLoading::create([
            'distributor_id'   => $distributor->id,
            'role'             => $role,
            'role_id'          => $roleId,
            'checker'          => $checker,
            'loading_date'     => date('Y-m-d'),
            'total_item_price' => $nilai,
            'note'             => $note . ' (stock opname by system)',
            'payment'          => 'by system',
        ]);

        GoodLoadingDetail::create([
            'good_loading_id' => $loading->id,
            'good_unit_id'    => $goodUnit->id,
            'last_stock'      => $lama,
            'quantity'        => $qty,
            'real_quantity'   => $qty * $goodUnit->unit->quantity,
            'price'           => $goodUnit->buy_price,
            'selling_price'   => $goodUnit->selling_price,
            'expiry_date'     => null,
        ]);
    }

    private static function transaksiKurang(string $role, $roleId, ?string $note, GoodUnit $goodUnit, float $lama, float $qty, float $nilai): void
    {
        $transaction = Transaction::create([
            'type'                 => 'stock_opname',
            'role'                 => $role,
            'role_id'              => $roleId,
            'member_id'            => '1',
            'total_item_price'     => $nilai,
            'total_discount_price' => 0,
            'total_sum_price'      => $nilai,
            'voucher'              => null,
            'voucher_nominal'      => null,
            'money_paid'           => $nilai,
            'money_returned'       => 0,
            'store'                => 'ntn getasan',
            'payment'              => 'stock_opname',
            'note'                 => $note . ' (stock opname by system)',
        ]);

        TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'good_unit_id'   => $goodUnit->id,
            'type'           => 'stock_opname',
            'quantity'       => $qty,
            'real_quantity'  => $qty * $goodUnit->unit->quantity,
            'last_stock'     => $lama,
            'buy_price'      => $goodUnit->buy_price,
            'selling_price'  => $goodUnit->buy_price,
            'discount_price' => null,
            'sum_price'      => $nilai,
        ]);
    }

    /** Satu jurnal untuk semua barang yang kurang (atau semua yang lebih). */
    private static function buatJurnal(StockOpname $opname, string $jenis, array $items): void
    {
        $total = round(array_sum(array_column($items, 'nilai')), 2);

        if ($total < 0.01) {
            return;
        }

        $persediaan = Account::where('code', '1141')->firstOrFail()->id;
        $penyusutan = Account::where('code', '5215')->firstOrFail()->id;

        Journal::create([
            'type'              => self::JURNAL,
            'type_id'           => $opname->id,
            'journal_date'      => date('Y-m-d'),
            'name'              => self::keterangan($opname, $jenis, $items, $total),
            'debit_account_id'  => $jenis === 'kurang' ? $penyusutan : $persediaan,
            'debit'             => $total,
            'credit_account_id' => $jenis === 'kurang' ? $persediaan : $penyusutan,
            'credit'            => $total,
        ]);
    }

    /**
     * Mis. "Rugi stock opname #12 28/11/2026: 47 barang Rp1.234.500 | Terbesar:
     * Rokok Surya 16 -12 pcs Rp300.000; Minyak 2L -2 pcs Rp150.000; ..."
     * Barang ditambahkan dari nilai terbesar selama masih muat di 255 karakter.
     */
    public static function keterangan(StockOpname $opname, string $jenis, array $items, float $total): string
    {
        $kepala = ($jenis === 'kurang' ? 'Rugi' : 'Lebih') . ' stock opname #' . $opname->id . ' ' . date('d/m/Y')
            . ': ' . count($items) . ' barang ' . self::rupiah($total);

        usort($items, fn ($a, $b) => $b['nilai'] <=> $a['nilai']);

        $teks    = $kepala . ' | Terbesar: ';
        $dipakai = 0;

        foreach ($items as $item) {
            $bagian = $item['nama'] . ' ' . self::angka($item['selisih'], true) . ' ' . $item['satuan'] . ' ' . self::rupiah($item['nilai']);
            $calon  = $teks . ($dipakai ? '; ' : '') . $bagian;

            if (mb_strlen($calon) > self::PANJANG_KETERANGAN) {
                break;
            }

            $teks = $calon;
            $dipakai++;
        }

        if ($dipakai === 0) {
            return mb_strimwidth($kepala, 0, self::PANJANG_KETERANGAN, '…');
        }

        if ($dipakai < count($items)) {
            $sisa = '; +' . (count($items) - $dipakai) . ' lainnya';
            if (mb_strlen($teks . $sisa) <= self::PANJANG_KETERANGAN) {
                $teks .= $sisa;
            }
        }

        return $teks;
    }

    public static function rupiah(float $nilai): string
    {
        return 'Rp' . number_format(round($nilai), 0, ',', '.');
    }

    /** 12 / 1,5 / -0,25 (tanpa nol di belakang koma). */
    public static function angka(float $qty, bool $tanda = false): string
    {
        $teks = rtrim(rtrim(number_format(abs($qty), 3, ',', '.'), '0'), ',');

        if ($tanda) {
            return ($qty > 0 ? '+' : ($qty < 0 ? '-' : '')) . $teks;
        }

        return ($qty < 0 ? '-' : '') . $teks;
    }

    // ------------------------------------------------------------------
    // Transaksi & loading hasil stock opname terkunci
    // ------------------------------------------------------------------

    public static function transaksiDariOpname(?Transaction $transaction): bool
    {
        return $transaction !== null && $transaction->type === 'stock_opname';
    }

    public static function loadingDariOpname(?GoodLoading $loading): bool
    {
        return $loading !== null
            && $loading->payment === 'by system'
            && optional(Distributor::find($loading->distributor_id))->name === self::DISTRIBUTOR;
    }

    /** Tolak edit/hapus transaksi hasil stock opname. */
    public static function tolakTransaksi($transactionId): void
    {
        if (self::transaksiDariOpname(Transaction::find($transactionId))) {
            throw ValidationException::withMessages(['stock_opname' => str_replace(':apa', 'Transaksi', self::PESAN_TERKUNCI)]);
        }
    }

    /** Tolak edit/hapus loading hasil stock opname. */
    public static function tolakLoading($goodLoadingId): void
    {
        if (self::loadingDariOpname(GoodLoading::find($goodLoadingId))) {
            throw ValidationException::withMessages(['stock_opname' => str_replace(':apa', 'Loading', self::PESAN_TERKUNCI)]);
        }
    }
}
