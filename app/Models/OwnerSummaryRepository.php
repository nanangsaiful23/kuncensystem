<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan Pemilik: the few numbers needed to take decisions.
 * Money (sales, profit, cash, receivables, payables, inventory) comes from
 * the journals, the same source as Neraca and Laba Rugi. Goods (what to buy,
 * capital stuck in slow goods, best earners) come from stock and sales.
 */
class OwnerSummaryRepository
{
    public function __construct(private GoodMovementRepository $movement)
    {
    }

    /**
     * Per month for the 12 months up to $date (the last month until $date):
     * penjualan, hpp, laba kotor, margin, beban, pendapatan lain, laba bersih
     * and the month-end balances of kas, piutang, persediaan and utang dagang.
     */
    public function monthlyTrend(Carbon $date): Collection
    {
        $first = $date->copy()->startOfMonth()->subMonths(11);
        $end = $date->toDateString();
        $accounts = DB::table('accounts')->get(['id', 'code', 'type', 'balance'])->keyBy('id');

        // debit - credit per account and month, without year-end closing journals
        $rows = DB::select("
            select account_id, ym, sum(amount) amount from (
                select debit_account_id account_id, date_format(journal_date, '%Y-%m') ym, debit amount
                  from journals where deleted_at is null and coalesce(type, '') <> 'closing' and journal_date <= ?
                union all
                select credit_account_id, date_format(journal_date, '%Y-%m'), -credit
                  from journals where deleted_at is null and coalesce(type, '') <> 'closing' and journal_date <= ?
            ) x group by account_id, ym", [$end, $end]);

        $months = [];
        for ($m = $first->copy(); $m->lte($date); $m->addMonth()) {
            $months[$m->format('Y-m')] = (object) [
                'ym' => $m->format('Y-m'), 'label' => $m->locale('id')->isoFormat('MMM YYYY'),
                'penjualan' => 0.0, 'hpp' => 0.0, 'beban' => 0.0, 'pendapatan_lain' => 0.0,
                'kas' => 0.0, 'piutang' => 0.0, 'persediaan' => 0.0, 'utang_dagang' => 0.0,
            ];
        }

        // balances: opening balance + every movement up to the month end
        $balanceGroups = ['kas' => '111', 'piutang' => '113', 'persediaan' => '1141', 'utang_dagang' => '2101'];
        $running = array_fill_keys(array_keys($balanceGroups), 0.0);
        foreach ($accounts as $a) {
            foreach ($balanceGroups as $key => $prefix) {
                if (str_starts_with((string) $a->code, $prefix)) {
                    $opening = (float) preg_replace('/[^0-9.\-]/', '', (string) $a->balance);
                    $running[$key] += str_starts_with(strtolower((string) $a->type), 'debet') ? $opening : -$opening;
                }
            }
        }

        usort($rows, fn ($x, $y) => strcmp($x->ym, $y->ym));
        $byMonth = [];
        foreach ($rows as $r) {
            $byMonth[$r->ym][] = $r;
        }
        ksort($byMonth);

        foreach ($byMonth as $ym => $list) {
            foreach ($list as $r) {
                $code = (string) ($accounts[$r->account_id]->code ?? '');
                $amount = (float) $r->amount; // debit - credit
                foreach ($balanceGroups as $key => $prefix) {
                    if (str_starts_with($code, $prefix)) {
                        $running[$key] += $amount;
                    }
                }
                if (! isset($months[$ym])) {
                    continue;
                }
                if (str_starts_with($code, '4')) {
                    $months[$ym]->penjualan -= $amount;
                } elseif ($code === '5101') {
                    $months[$ym]->hpp += $amount;
                } elseif (str_starts_with($code, '5')) {
                    $months[$ym]->beban += $amount;
                } elseif (str_starts_with($code, '6')) {
                    $months[$ym]->pendapatan_lain -= $amount;
                }
            }
            if (isset($months[$ym])) {
                $months[$ym]->kas = $running['kas'];
                $months[$ym]->piutang = $running['piutang'];
                $months[$ym]->persediaan = $running['persediaan'];
                $months[$ym]->utang_dagang = -$running['utang_dagang'];
            }
        }

        // months without journals keep the balance of the month before
        $last = null;
        foreach ($months as $m) {
            if ($last && $m->kas == 0 && $m->persediaan == 0 && $m->penjualan == 0) {
                foreach (['kas', 'piutang', 'persediaan', 'utang_dagang'] as $k) {
                    $m->$k = $last->$k;
                }
            }
            $m->laba_kotor = $m->penjualan - $m->hpp;
            $m->margin = $m->penjualan > 0 ? round($m->laba_kotor / $m->penjualan * 100, 1) : 0;
            $m->laba_bersih = $m->laba_kotor - $m->beban + $m->pendapatan_lain;
            $last = $m;
        }

        return collect(array_values($months));
    }

    /**
     * Every active good with its stock (base unit), base unit price, value,
     * last distributor and its sales in the last $days days (in base units).
     */
    public function goodsWithSales(Carbon $date, int $days): Collection
    {
        $from = $date->copy()->subDays($days - 1)->startOfDay();
        $sales = $this->movement->salesPerGood($from, $date->copy()->endOfDay())
            ->selectRaw('MAX(transactions.created_at) AS last_sale')
            ->get()->keyBy('good_id');
        $longAgo = $this->movement->salesPerGood($date->copy()->subDays(59)->startOfDay(), $date->copy()->endOfDay())
            ->get()->keyBy('good_id');

        return Good::leftJoin('good_units AS gu', 'gu.id', '=', 'goods.base_unit_id')
            ->leftJoin('units AS u', 'u.id', '=', 'gu.unit_id')
            ->leftJoin('types', 'types.id', '=', 'goods.type_id')
            ->leftJoin('distributors AS d', 'd.id', '=', 'goods.last_distributor_id')
            ->whereNull('goods.deleted_at')
            ->where('goods.is_discontinued', 0)
            ->get(['goods.id', 'goods.code', 'goods.name', 'types.name AS tipe', 'goods.last_stock', 'goods.last_loading',
                   'goods.last_transaction', 'u.name AS satuan', 'u.quantity AS base_qty', 'gu.buy_price', 'gu.selling_price',
                   'd.name AS distributor'])
            ->map(function ($g) use ($sales, $longAgo, $days) {
                $baseQty = (float) $g->base_qty > 0 ? (float) $g->base_qty : 1;
                $s = $sales->get($g->id);
                $sold = $s ? (float) $s->total_qty_terjual / $baseQty : 0;
                $perDay = $sold / $days;
                $stock = (float) $g->last_stock;
                $tipe = trim((string) $g->tipe);

                return (object) [
                    'id' => $g->id, 'code' => $g->code,
                    'name' => ($tipe === '' || $tipe === '-') ? ucfirst($g->name) : ucfirst(strtolower($tipe) . ' ' . $g->name),
                    'satuan' => $g->satuan ?? '-', 'stock' => $stock,
                    'buy_price' => (float) $g->buy_price, 'value' => $stock * (float) $g->buy_price,
                    'distributor' => $g->distributor ?? '-',
                    'sold' => round($sold, 2), 'per_day' => $perDay,
                    'days_left' => $perDay > 0 ? $stock / $perDay : null,
                    'omzet' => $s ? (float) $s->total_omzet : 0.0, 'laba' => $s ? (float) $s->total_laba : 0.0,
                    'sold_60' => isset($longAgo[$g->id]),
                    'last_sale' => $s->last_sale ?? $g->last_transaction,
                    'last_loading' => $g->last_loading,
                ];
            });
    }

    public function summary(Carbon $date): array
    {
        $trend = $this->monthlyTrend($date);
        $goods = $this->goodsWithSales($date, 30);
        $now = $trend->last();
        $prev = $trend->count() > 1 ? $trend[$trend->count() - 2] : null;

        // HPP per day over the last 30 days -> how many days the stock lasts
        $hpp30 = (float) DB::table('journals')->join('accounts', 'accounts.id', '=', 'journals.debit_account_id')
            ->where('accounts.code', '5101')->whereNull('journals.deleted_at')
            ->whereBetween('journal_date', [$date->copy()->subDays(29)->toDateString(), $date->toDateString()])->sum('debit')
            - (float) DB::table('journals')->join('accounts', 'accounts.id', '=', 'journals.credit_account_id')
            ->where('accounts.code', '5101')->whereNull('journals.deleted_at')
            ->whereBetween('journal_date', [$date->copy()->subDays(29)->toDateString(), $date->toDateString()])->sum('credit');
        $stockValue = (float) $goods->sum('value');

        $toBuy = $goods->filter(fn ($g) => $g->days_left !== null && $g->days_left < 7)
            ->sortBy('days_left')->values();
        $stuck = $goods->filter(fn ($g) => $g->value > 0 && ! $g->sold_60)
            ->sortByDesc('value')->values();
        $earners = $goods->filter(fn ($g) => $g->laba != 0)->sortByDesc('laba')->values();
        $negative = $goods->filter(fn ($g) => $g->stock < 0)->sortBy('stock')->values();

        $purchases = GoodLoading::join('distributors', 'distributors.id', '=', 'good_loadings.distributor_id')
            ->whereNull('good_loadings.deleted_at')
            ->whereBetween('good_loadings.loading_date', [$date->copy()->subDays(89)->toDateString(), $date->toDateString()])
            ->selectRaw('distributors.name, COUNT(*) loadings, SUM(good_loadings.total_item_price) total, MAX(good_loadings.loading_date) last_date')
            ->groupBy('distributors.id', 'distributors.name')->orderByDesc('total')->limit(15)->get();

        return [
            'date' => $date, 'trend' => $trend, 'now' => $now, 'prev' => $prev,
            'stockValue' => $stockValue, 'stockDays' => $hpp30 > 0 ? $stockValue / ($hpp30 / 30) : null,
            'toBuy' => $toBuy, 'stuck' => $stuck, 'stuckValue' => (float) $stuck->sum('value'),
            'earners' => $earners, 'negative' => $negative, 'purchases' => $purchases,
        ];
    }
}
