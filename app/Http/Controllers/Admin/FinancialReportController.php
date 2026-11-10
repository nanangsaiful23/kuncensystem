<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\FinancialReportRepository;

class FinancialReportController extends Controller
{
    protected $repo;

    public function __construct(FinancialReportRepository $repo)
    {
        $this->repo = $repo;
    }

    private function colSum($collection, string $field): float
    {
        if (!$collection || $collection->isEmpty()) return 0.0;
        return (float) $collection->reduce(function ($carry, $item) use ($field) {
            $val = is_array($item) ? ($item[$field] ?? 0) : ($item->{$field} ?? 0);
            return $carry + (float) $val;
        }, 0.0);
    }

    public function index(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->get('end_date',   Carbon::now()->toDateString());
        $tab       = $request->get('tab', 'labarugi');

        $accountsGrouped = $this->repo->getAccountsGrouped();
        $allAccounts     = $this->repo->getAllAccounts();

        $generalJournal  = collect();
        $ledger          = collect();
        $profitLoss      = [];
        $balanceSheet    = [];
        $cashFlow        = collect();
        $monthlyBalance  = [];

        // Hanya load data tab yang aktif (hemat query)
        switch ($tab) {
            case 'jurnal':
                $generalJournal = $this->repo->getGeneralJournal($startDate, $endDate);
                break;
            case 'buku_besar':
                $ledger = $this->repo->getLedgerByAccount($startDate, $endDate);
                break;
            case 'neraca':
                $balanceSheet = $this->repo->getBalanceSheet($endDate);
                break;
            case 'neraca_bulanan':
                $monthlyBalance = $this->repo->getMonthlyBalanceSheet((int) $request->get('year', date('Y')));
                break;
            case 'kas':
                $cashFlow = $this->repo->getCashFlowSummary($startDate, $endDate);
                break;
            case 'labarugi':
            default:
                $profitLoss = $this->repo->getProfitLossReport($startDate, $endDate);
                break;
        }

        return view('admin.financial-report', compact(
            'startDate', 'endDate', 'tab',
            'allAccounts', 'accountsGrouped',
            'generalJournal', 'ledger',
            'profitLoss', 'balanceSheet', 'cashFlow', 'monthlyBalance'
        ));
    }

    /** Tutup buku a year (POST year). Only for roles in kuncen.period_unlock_roles. */
    public function closeYear(Request $request)
    {
        abort_unless(\App\Services\PeriodLock::userCanUnlock(), 403, 'Hanya super admin yang bisa tutup buku.');
        $year = (int) $request->input('year');

        if ($request->input('action') === 'reopen') {
            \App\Services\YearClosing::reopen($year);
            $message = "Tahun $year dibuka kembali.";
        } else {
            $profit = \App\Services\YearClosing::close($year);
            $message = "Tutup buku $year selesai. Laba Rp " . number_format($profit, 0, ',', '.') . ' dipindah ke Laba Ditahan.';
        }

        return redirect()->route('admin.reports.financial', ['tab' => 'neraca_bulanan', 'year' => $year])->with('closing_message', $message);
    }
}
