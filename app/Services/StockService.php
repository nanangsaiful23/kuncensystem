<?php

namespace App\Services;

use App\Models\Good;
use App\Models\GoodUnit;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for stock.
 *
 * Stock is always derived from history:
 *   loadings     = good_loading_details of loadings that are not deleted
 *   transactions = transaction_details (except type 'retur') of transactions
 *                  that are not deleted
 *
 * goods.total_loading / total_transaction / last_stock are only a cache of
 * that history, expressed in the good's BASE unit (the unit the rest of the
 * app already uses for last_stock). They are recalculated from history after
 * every change instead of being incremented by hand, so they can no longer
 * drift away from getStock().
 */
class StockService
{
    /** @var array<int, true> goods waiting to be recalculated */
    private static array $queue = [];

    /**
     * Raw history in the smallest unit (real_quantity): [loaded, sold].
     */
    public static function history(int $goodId): array
    {
        // Deleted loadings/transactions are few. Excluding them with a literal id
        // list lets MySQL read only the covering index of the detail table
        // (idx_td_stock / idx_gld_stock) instead of joining the parent table once
        // per detail row, which took seconds per good on a big history.
        $deletedLoadings     = DB::table('good_loadings')->whereNotNull('deleted_at')->pluck('id')->all();
        $deletedTransactions = DB::table('transactions')->whereNotNull('deleted_at')->pluck('id')->all();

        $loaded = DB::table('good_loading_details')
            ->join('good_units', 'good_units.id', '=', 'good_loading_details.good_unit_id')
            ->where('good_units.good_id', $goodId)
            ->whereNull('good_loading_details.deleted_at')
            ->tap(fn ($q) => self::kecuali($q, 'good_loading_details.good_loading_id', $deletedLoadings))
            ->sum('good_loading_details.real_quantity');

        $sold = DB::table('transaction_details')
            ->join('good_units', 'good_units.id', '=', 'transaction_details.good_unit_id')
            ->where('good_units.good_id', $goodId)
            ->whereRaw("coalesce(transaction_details.type, '') <> 'retur'")
            ->whereNull('transaction_details.deleted_at')
            ->tap(fn ($q) => self::kecuali($q, 'transaction_details.transaction_id', $deletedTransactions))
            ->sum('transaction_details.real_quantity');

        return [(float) $loaded, (float) $sold];
    }

    /**
     * whereNotIn per 500 id. MariaDB mengubah (NOT) IN (...) berisi 1000 nilai
     * atau lebih menjadi subquery, dan di server lokal (10.4) hasilnya malah
     * kosong: semua loading/penjualan tidak terhitung dan stok jadi salah.
     */
    private static function kecuali($query, string $kolom, array $ids): void
    {
        foreach (array_chunk($ids, 500) as $chunk) {
            $query->whereNotIn($kolom, $chunk);
        }
    }

    /** Quantity (in pieces) of the unit last_stock is counted in. */
    public static function baseQuantity(Good $good): float
    {
        $unit = $good->base_unit();

        return $unit && $unit->unit ? max((float) $unit->unit->quantity, 0.000001) : 1.0;
    }

    /** Quantity (in pieces) of the smallest unit, used by getStock(). */
    public static function smallestQuantity(Good $good): float
    {
        $unit = $good->getPcsSellingPrice();

        return $unit && $unit->unit ? max((float) $unit->unit->quantity, 0.000001) : 1.0;
    }

    /** Stock in the smallest unit, same meaning as the old getStock(). */
    public static function stock(Good $good): float
    {
        [$loaded, $sold] = self::history($good->id);

        return round(($loaded - $sold) / self::smallestQuantity($good), 3);
    }

    /** Counter values for the goods table, in the base unit. */
    public static function counters(Good $good): array
    {
        [$loaded, $sold] = self::history($good->id);
        $base = self::baseQuantity($good);

        $totalLoading     = round($loaded / $base, 2);
        $totalTransaction = round($sold / $base, 2);

        return [
            'total_loading'     => $totalLoading,
            'total_transaction' => $totalTransaction,
            'last_stock'        => round($totalLoading - $totalTransaction, 2),
        ];
    }

    /**
     * counters() for many goods at once: a few grouped queries instead of
     * several per good. Same rules as history() and baseQuantity().
     * Used by the daily check after Tutup Kasir (App\Services\CekKecocokan).
     *
     * @param  int[]  $goodIds
     * @return array<int, array{total_loading: float, total_transaction: float, last_stock: float}>
     */
    public static function countersMany(array $goodIds): array
    {
        $goodIds = array_values(array_unique(array_map('intval', $goodIds)));
        if (! $goodIds) {
            return [];
        }

        $deletedLoadings     = DB::table('good_loadings')->whereNotNull('deleted_at')->pluck('id')->all();
        $deletedTransactions = DB::table('transactions')->whereNotNull('deleted_at')->pluck('id')->all();

        $loaded = $sold = [];
        foreach (array_chunk($goodIds, 500) as $ids) {
            $loaded += DB::table('good_loading_details')
                ->join('good_units', 'good_units.id', '=', 'good_loading_details.good_unit_id')
                ->whereIn('good_units.good_id', $ids)
                ->whereNull('good_loading_details.deleted_at')
                ->tap(fn ($q) => self::kecuali($q, 'good_loading_details.good_loading_id', $deletedLoadings))
                ->groupBy('good_units.good_id')
                ->selectRaw('good_units.good_id AS g, SUM(good_loading_details.real_quantity) AS q')
                ->pluck('q', 'g')->all();

            $sold += DB::table('transaction_details')
                ->join('good_units', 'good_units.id', '=', 'transaction_details.good_unit_id')
                ->whereIn('good_units.good_id', $ids)
                ->whereRaw("coalesce(transaction_details.type, '') <> 'retur'")
                ->whereNull('transaction_details.deleted_at')
                ->tap(fn ($q) => self::kecuali($q, 'transaction_details.transaction_id', $deletedTransactions))
                ->groupBy('good_units.good_id')
                ->selectRaw('good_units.good_id AS g, SUM(transaction_details.real_quantity) AS q')
                ->pluck('q', 'g')->all();
        }

        $base = self::baseQuantities($goodIds);
        $hasil = [];
        foreach ($goodIds as $id) {
            $totalLoading     = round((float) ($loaded[$id] ?? 0) / $base[$id], 2);
            $totalTransaction = round((float) ($sold[$id] ?? 0) / $base[$id], 2);
            $hasil[$id] = [
                'total_loading'     => $totalLoading,
                'total_transaction' => $totalTransaction,
                'last_stock'        => round($totalLoading - $totalTransaction, 2),
            ];
        }

        return $hasil;
    }

    /**
     * baseQuantity() for many goods: the unit in goods.base_unit_id, else the
     * unit with quantity "1", else the smallest unit (Good::base_unit() and
     * Good::getPcsSellingPrice()). Deleted good units do not count; a deleted
     * unit counts as 1, like a missing relation in baseQuantity().
     *
     * @return array<int, float>
     */
    private static function baseQuantities(array $goodIds): array
    {
        // Per 500 id: MariaDB mengubah IN (...) berisi 1000 nilai atau lebih
        // menjadi subquery, dan di server lokal hasilnya malah kosong.
        $units = collect();
        $baseUnitIds = [];
        foreach (array_chunk($goodIds, 500) as $ids) {
            $units = $units->merge(DB::table('good_units')
                ->leftJoin('units', 'units.id', '=', 'good_units.unit_id')
                ->whereIn('good_units.good_id', $ids)
                ->whereNull('good_units.deleted_at')
                ->orderBy('good_units.id')
                ->get(['good_units.id', 'good_units.good_id', 'units.id AS unit_id', 'units.quantity', 'units.deleted_at AS unit_deleted_at']));
            $baseUnitIds += DB::table('goods')->whereIn('id', $ids)->pluck('base_unit_id', 'id')->all();
        }
        $byId = $units->keyBy('id');
        $perGood = $units->groupBy('good_id');

        $hasil = [];
        foreach ($goodIds as $id) {
            $list = $perGood[$id] ?? collect();
            $unit = ! empty($baseUnitIds[$id]) ? ($byId[$baseUnitIds[$id]] ?? null) : null;
            $unit ??= $list->first(fn ($u) => (string) $u->quantity === '1');
            $unit ??= $list->sortBy(fn ($u) => (int) $u->quantity)->first();

            $hasil[$id] = $unit && $unit->unit_id !== null && $unit->unit_deleted_at === null
                ? max((float) $unit->quantity, 0.000001) : 1.0;
        }

        return $hasil;
    }

    /**
     * Recalculate one good right now. Writes with the query builder so no
     * model events fire. Returns [old counters, new counters].
     */
    public static function sync(int $goodId): ?array
    {
        $good = Good::withTrashed()->find($goodId);

        if ($good === null) {
            return null;
        }

        $old = [
            'total_loading'     => round((float) $good->total_loading, 2),
            'total_transaction' => round((float) $good->total_transaction, 2),
            'last_stock'        => round((float) $good->last_stock, 2),
        ];
        $new = self::counters($good);

        if ($old != $new) {
            DB::table('goods')->where('id', $goodId)->update($new);
        }

        return [$old, $new];
    }

    /**
     * Ask for a recalculation once the current database transaction has
     * committed (immediately when there is none). Running after the commit
     * means the sums see every committed row, so two cashiers selling the same
     * good at the same moment still end with the right stock.
     */
    public static function queue(?int $goodId): void
    {
        if (! $goodId) {
            return;
        }

        self::$queue[$goodId] = true;

        DB::afterCommit(fn () => self::flush());
    }

    public static function queueGoodUnit(?int $goodUnitId): void
    {
        if (! $goodUnitId) {
            return;
        }

        self::queue(GoodUnit::withTrashed()->whereKey($goodUnitId)->value('good_id'));
    }

    public static function flush(): void
    {
        $ids = array_keys(self::$queue);
        self::$queue = [];

        foreach ($ids as $id) {
            self::sync($id);
        }
    }
}
