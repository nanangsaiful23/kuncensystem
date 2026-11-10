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
        $loaded = DB::table('good_loading_details')
            ->join('good_units', 'good_units.id', '=', 'good_loading_details.good_unit_id')
            ->join('good_loadings', 'good_loadings.id', '=', 'good_loading_details.good_loading_id')
            ->where('good_units.good_id', $goodId)
            ->whereNull('good_loadings.deleted_at')
            ->whereNull('good_loading_details.deleted_at')
            ->sum('good_loading_details.real_quantity');

        $sold = DB::table('transaction_details')
            ->join('good_units', 'good_units.id', '=', 'transaction_details.good_unit_id')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->where('good_units.good_id', $goodId)
            ->whereRaw("coalesce(transaction_details.type, '') <> 'retur'")
            ->whereNull('transactions.deleted_at')
            ->whereNull('transaction_details.deleted_at')
            ->sum('transaction_details.real_quantity');

        return [(float) $loaded, (float) $sold];
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
