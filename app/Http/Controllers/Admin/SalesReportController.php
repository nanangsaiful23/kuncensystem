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
     * Halaman utama laporan penjualan/keuangan
     * GET /admin/reports/sales
     */
    public function index(Request $request)
    {
        // Default: bulan ini
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->get('end_date',   Carbon::now()->toDateString());
        $year      = $request->get('year', now()->year);

        // ── KPI Utama ────────────────────────────────────────────────────────
        $summary = $this->repo->getSalesSummary($startDate, $endDate);

        // // ── Trend Penjualan ──────────────────────────────────────────────────
        // $dailyTrend   = $this->repo->getDailySalesTrend($startDate, $endDate);
        // $monthlyTrend = $this->repo->getMonthlySalesTrend($year);

        // // ── Produk ───────────────────────────────────────────────────────────
        $topGoods       = $this->repo->getTopSellingGoods($startDate, $endDate, 10);
        // $salesByCategory = $this->repo->getSalesByCategory($startDate, $endDate);
        // $salesByBrand   = $this->repo->getSalesByBrand($startDate, $endDate);

        // // ── Pelanggan ────────────────────────────────────────────────────────
        // $topMembers        = $this->repo->getTopMembers($startDate, $endDate, 10);
        // $memberReceivables = $this->repo->getMemberReceivables();

        // // ── Pembelian & Stok ─────────────────────────────────────────────────
        // $purchaseByDistributor = $this->repo->getPurchaseSummaryByDistributor($startDate, $endDate);
        // $stockValuation        = $this->repo->getCurrentStockValuation();
        // $returSummary          = $this->repo->getReturSummary($startDate, $endDate);

        // // ── Keuangan ─────────────────────────────────────────────────────────
        // $profitLoss      = $this->repo->getProfitLossSummary($startDate, $endDate);
        // $paymentMethods  = $this->repo->getSalesByPaymentMethod($startDate, $endDate);
        // $voucherReport   = $this->repo->getVoucherUsageReport($startDate, $endDate);
        // $generalJournal  = $this->repo->getGeneralJournal($startDate, $endDate);
        // $trialBalance    = $this->repo->getTrialBalance();
        


        // ── Trend Penjualan ──────────────────────────────────────────────────
        $dailyTrend   = $this->repo->getDailySalesTrend($startDate, $endDate);
        $monthlyTrend = $this->repo->getMonthlySalesTrend($year);

        // ── Produk ───────────────────────────────────────────────────────────
        // $topGoods       = [];
        $salesByCategory = [];
        $salesByBrand   = [];

        // ── Pelanggan ────────────────────────────────────────────────────────
        $topMembers        =[];
        $memberReceivables = [];

        // ── Pembelian & Stok ─────────────────────────────────────────────────
        $purchaseByDistributor = $this->repo->getPurchaseSummaryByDistributor($startDate, $endDate);
        $stockValuation        = $this->repo->getCurrentStockValuation();
        $returSummary          = $this->repo->getReturSummary($startDate, $endDate);

        // ── Keuangan ─────────────────────────────────────────────────────────
        $profitLoss      = $this->repo->getProfitLossSummary($startDate, $endDate);
        $paymentMethods  = $this->repo->getSalesByPaymentMethod($startDate, $endDate);
        $voucherReport   = $this->repo->getTrialBalance();
        $generalJournal  = $this->repo->getGeneralJournal($startDate, $endDate);
        $trialBalance    = $this->repo->getTrialBalance();
        
       
        // Tahun tersedia untuk dropdown
        $availableYears = range(now()->year, now()->year - 4);

        return view('admin.sales-report', compact(
            'startDate', 'endDate', 'year', 'availableYears',
            'summary',
            'dailyTrend', 'monthlyTrend',
            'topGoods', 'salesByCategory', 'salesByBrand',
            'topMembers', 'memberReceivables',
            'purchaseByDistributor', 'stockValuation', 'returSummary',
            'profitLoss', 'paymentMethods', 'voucherReport',
            'generalJournal', 'trialBalance'
        ));
    }
}