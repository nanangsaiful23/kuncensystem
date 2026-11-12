<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rincian stok KELUAR per penyebab.
 *
 * StockService hanya tahu "total keluar" (semua transaction_details kecuali
 * tipe 'retur'). Kelas ini memecah angka yang sama menjadi:
 *
 *   terjual  penjualan ke pembeli
 *   retur    dikembalikan ke distributor          (tipe 'retur_item')
 *   expired  kadaluarsa                            (5215 + loss_reason expired)
 *   rusak    rusak / cacat                         (5215 + loss_reason rusak)
 *   hilang   hilang & selisih kurang stock opname  (5215 lainnya, 'stock_opname')
 *   lainnya  pemakaian internal, dibatalkan, dll.
 *
 * Jumlah semua penyebab = total keluar di StockService, jadi rinciannya
 * selalu cocok dengan goods.total_transaction.
 */
class StockOutflow
{
    const LABELS = [
        'terjual' => 'Terjual',
        'retur'   => 'Retur ke distributor',
        'expired' => 'Expired',
        'rusak'   => 'Rusak',
        'hilang'  => 'Hilang / selisih opname',
        'lainnya' => 'Pemakaian internal & lainnya',
    ];

    /** Pilihan penyebab pada transaksi 5215 (Biaya Penyusutan Barang). */
    const LOSS_REASONS = [
        'hilang'  => 'Hilang',
        'expired' => 'Expired / kadaluarsa',
        'rusak'   => 'Rusak / cacat',
    ];

    /** Penyebab yang berarti barang keluar BUKAN karena laku. */
    const NON_SALE = ['retur', 'expired', 'rusak', 'hilang'];

    private static ?bool $hasLossReason = null;

    /** false selama migrasi kolom transactions.loss_reason belum dijalankan. */
    public static function hasLossReason(): bool
    {
        if (self::$hasLossReason === null) {
            self::$hasLossReason = Schema::hasColumn('transactions', 'loss_reason');
        }

        return self::$hasLossReason;
    }

    public static function lossReason(?string $value): string
    {
        return isset(self::LOSS_REASONS[$value]) ? $value : 'hilang';
    }

    public static function lossReasonLabel(?string $value): string
    {
        return self::LOSS_REASONS[self::lossReason($value)];
    }

    /** Ekspresi SQL yang menggolongkan satu baris transaction_details. */
    public static function reasonSql(): string
    {
        $loss = self::hasLossReason()
            ? "CASE WHEN transactions.loss_reason IN ('expired', 'rusak') THEN transactions.loss_reason ELSE 'hilang' END"
            : "'hilang'";

        return "CASE
            WHEN transaction_details.type = 'retur_item' THEN 'retur'
            WHEN transaction_details.type = '5215' THEN {$loss}
            WHEN transaction_details.type = 'stock_opname' THEN 'hilang'
            WHEN transaction_details.type = 'normal' AND transactions.type IN ('normal', 'retur') THEN 'terjual'
            ELSE 'lainnya'
        END";
    }

    /**
     * Jumlah keluar per barang per penyebab, dalam satuan terkecil
     * (real_quantity), sama seperti StockService::history().
     *
     * @param  array|null   $goodIds  null = semua barang
     * @param  mixed        $start    awal periode (null = sejak awal)
     * @param  mixed        $end      akhir periode (null = sampai sekarang)
     * @return array<int, array<string, float>>  [good_id => [penyebab => qty]]
     */
    public static function byGood(?array $goodIds = null, $start = null, $end = null): array
    {
        if ($goodIds !== null && count($goodIds) === 0) {
            return [];
        }

        $query = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('good_units', 'good_units.id', '=', 'transaction_details.good_unit_id')
            ->whereRaw("coalesce(transaction_details.type, '') <> 'retur'")
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_details.deleted_at');

        if ($goodIds !== null) {
            $query->whereIn('good_units.good_id', $goodIds);
        }
        if ($start !== null) {
            $query->where('transactions.created_at', '>=', $start);
        }
        if ($end !== null) {
            $query->where('transactions.created_at', '<=', $end);
        }

        $rows = $query
            ->select(
                'good_units.good_id',
                DB::raw(self::reasonSql() . ' AS reason'),
                DB::raw('SUM(transaction_details.real_quantity) AS qty')
            )
            ->groupBy('good_units.good_id', 'reason')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->good_id] ??= self::emptyRow();
            $result[$row->good_id][$row->reason] = (float) $row->qty;
        }

        return $result;
    }

    /**
     * Rincian untuk ditampilkan di daftar/detail barang: dalam satuan dasar
     * barang (satuan yang sama dengan goods.last_stock).
     *
     * @param  iterable  $goods  koleksi App\Models\Good
     * @return array<int, array<string, float>>
     */
    public static function forDisplay(iterable $goods): array
    {
        $byId = [];
        foreach ($goods as $good) {
            $byId[$good->id] = $good;
        }

        $raw    = self::byGood(array_keys($byId));
        $result = [];

        foreach ($byId as $id => $good) {
            $base = StockService::baseQuantity($good);
            $row  = $raw[$id] ?? self::emptyRow();

            foreach ($row as $reason => $qty) {
                $row[$reason] = round($qty / $base, 2);
            }

            $result[$id] = $row;
        }

        return $result;
    }

    /** Rincian satu barang sebagai teks HTML pendek, mis. untuk hasil pencarian. */
    public static function summary(array $row, string $unit): string
    {
        $parts = [];
        foreach (self::LABELS as $reason => $label) {
            if (($row[$reason] ?? 0) != 0) {
                $parts[] = e($label . ': ' . ($row[$reason] + 0) . ' ' . $unit);
            }
        }

        return implode('<br>', $parts);
    }

    public static function emptyRow(): array
    {
        return array_fill_keys(array_keys(self::LABELS), 0.0);
    }

    /** Jumlah keluar yang bukan karena laku. */
    public static function nonSale(array $row): float
    {
        $total = 0.0;
        foreach (self::NON_SALE as $reason) {
            $total += $row[$reason] ?? 0;
        }

        return $total;
    }
}
