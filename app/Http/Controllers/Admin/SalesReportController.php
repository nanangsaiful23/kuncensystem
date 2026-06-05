<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\SalesReportRepository ;
class SalesReportController extends Controller
{
    protected $repo;
 
    public function __construct(SalesReportRepository $repo)
    {
        $this->repo = $repo;
    }
 
    /**
     * Hitung total kolom dari collection apapun.
     * Aman untuk: Eloquent model, stdClass, array, maupun collection kosong.
     */
    private function colSum($collection, string $field): float
    {
        if (!$collection || $collection->isEmpty()) {
            return 0.0;
        }
        return (float) $collection->reduce(function ($carry, $item) use ($field) {
            $val = is_array($item) ? ($item[$field] ?? 0) : ($item->{$field} ?? 0);
            return $carry + (float) $val;
        }, 0.0);
    }
 
    /**
     * Pastikan nilai kembalian repo selalu Collection (tidak pernah null).
     * Jika repo melempar exception, kembalikan collection kosong.
     */
    private function safe(callable $fn): \Illuminate\Support\Collection
    {
        try {
            $result = $fn();
            // Jika null atau bukan collection, kembalikan collect()
            if (is_null($result)) {
                return collect();
            }
            // Eloquent collection / Support collection → langsung return
            if ($result instanceof \Illuminate\Support\Collection) {
                return $result;
            }
            // Jika array, wrap ke collection
            if (is_array($result)) {
                return collect($result);
            }
            return collect();
        } catch (\Throwable $e) {
            \Log::error('SalesReport error: ' . $e->getMessage());
            return collect();
        }
    }
 
    /**
     * Pastikan nilai kembalian repo array (untuk getSalesSummary, getProfitLoss)
     * tidak pernah null.
     */
    private function safeArray(callable $fn, array $default): array
    {
        try {
            $result = $fn();
            return is_array($result) ? $result : $default;
        } catch (\Throwable $e) {
            \Log::error('SalesReport error: ' . $e->getMessage());
            return $default;
        }
    }
 
    /**
     * Halaman utama laporan penjualan/keuangan
     * GET /admin/reports/sales
     */
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->get('end_date',   Carbon::now()->toDateString());
        $year      = $request->get('year', now()->year);
 
        // Default array kosong untuk summary & profitLoss (kembalian berupa array)
        $emptySummary = [
            'totalOmzet' => 0, 'totalDiskon' => 0, 'totalHpp' => 0,
            'totalLaba'  => 0, 'totalTransaksi' => 0, 'rataRata' => 0,
        ];
        $emptyPL = [
            'omzetBruto' => 0, 'totalDiskon' => 0, 'totalRetur' => 0,
            'omzetBersih'=> 0, 'totalHpp'    => 0, 'labaKotor'  => 0,
            'totalPembelian' => 0,
        ];
 
        // ── Semua data dengan guard kosong ────────────────────────────────────
        $summary     = $this->safeArray(fn() => $this->repo->getSalesSummary($startDate, $endDate), $emptySummary);
        $profitLoss  = $this->safeArray(fn() => $this->repo->getProfitLossSummary($startDate, $endDate), $emptyPL);
 
        $dailyTrend            = $this->safe(fn() => $this->repo->getDailySalesTrend($startDate, $endDate));
        $monthlyTrend          = $this->safe(fn() => $this->repo->getMonthlySalesTrend($year));
        $topGoods              = $this->safe(fn() => $this->repo->getTopSellingGoods($startDate, $endDate, 10));
        $salesPerGood          = $this->safe(fn() => $this->repo->getSalesPerGood($startDate, $endDate, 20));
        $salesByCategory       = $this->safe(fn() => $this->repo->getSalesByCategory($startDate, $endDate));
        $salesByBrand          = $this->safe(fn() => $this->repo->getSalesByBrand($startDate, $endDate));
        $topMembers            = $this->safe(fn() => $this->repo->getTopMembers($startDate, $endDate, 10));
        $memberReceivables     = $this->safe(fn() => $this->repo->getMemberReceivables());
        $purchaseByDistributor = $this->safe(fn() => $this->repo->getPurchaseSummaryByDistributor($startDate, $endDate));
        $stockValuation        = $this->safe(fn() => $this->repo->getCurrentStockValuation());
        $returSummary          = $this->safe(fn() => $this->repo->getReturSummary($startDate, $endDate));
        $paymentMethods        = $this->safe(fn() => $this->repo->getSalesByPaymentMethod($startDate, $endDate));
        $voucherReport         = $this->safe(fn() => $this->repo->getVoucherUsageReport($startDate, $endDate));
        $generalJournal        = $this->safe(fn() => $this->repo->getGeneralJournal($startDate, $endDate));
        $trialBalance          = $this->safe(fn() => $this->repo->getTrialBalance());
 
        $availableYears = range(now()->year, now()->year - 4);
 
        // ── Pre-hitung semua total (colSum aman untuk collection kosong) ───────
        $totals = [
            'monthly_omzet'      => $this->colSum($monthlyTrend, 'omzet'),
            'monthly_diskon'     => $this->colSum($monthlyTrend, 'total_diskon'),
            'monthly_transaksi'  => $this->colSum($monthlyTrend, 'jumlah_transaksi'),
 
            'topgoods_omzet'     => $this->colSum($topGoods, 'total_omzet'),
            'topgoods_laba'      => $this->colSum($topGoods, 'total_laba'),
 
            'cat_omzet'          => $this->colSum($salesByCategory, 'total_omzet'),
            'brand_omzet'        => $this->colSum($salesByBrand, 'total_omzet'),
            'payment_omzet'      => $this->colSum($paymentMethods, 'total_omzet'),
 
            'recv_tagihan'       => $this->colSum($memberReceivables, 'total_tagihan'),
            'recv_dibayar'       => $this->colSum($memberReceivables, 'total_pembayaran'),
            'recv_sisa'          => $this->colSum($memberReceivables, 'sisa_piutang'),
 
            'dist_loading'       => $this->colSum($purchaseByDistributor, 'jumlah_loading'),
            'dist_pembelian'     => $this->colSum($purchaseByDistributor, 'total_pembelian'),
 
            'stock_nilai_hpp'    => $this->colSum($stockValuation, 'nilai_hpp'),
            'stock_nilai_jual'   => $this->colSum($stockValuation, 'nilai_jual'),
            'stock_potensi_laba' => $this->colSum($stockValuation, 'potensi_laba'),
 
            'journal_debit'      => $this->colSum($generalJournal, 'debit'),
            'journal_credit'     => $this->colSum($generalJournal, 'credit'),
        ];
 
        $totals['monthly_bersih']  = $totals['monthly_omzet'] - $totals['monthly_diskon'];
        $totals['topgoods_margin'] = $totals['topgoods_omzet'] > 0
            ? round($totals['topgoods_laba'] / $totals['topgoods_omzet'] * 100, 1)
            : 0;
 
        return view('admin.sales-report', compact(
            'startDate', 'endDate', 'year', 'availableYears',
            'summary', 'totals',
            'dailyTrend', 'monthlyTrend',
            'topGoods', 'salesPerGood', 'salesByCategory', 'salesByBrand',
            'topMembers', 'memberReceivables',
            'purchaseByDistributor', 'stockValuation', 'returSummary',
            'profitLoss', 'paymentMethods', 'voucherReport',
            'generalJournal', 'trialBalance'
        ));
    }
}