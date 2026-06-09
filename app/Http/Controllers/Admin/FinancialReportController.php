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
            'profitLoss', 'balanceSheet', 'cashFlow'
        ));
    }
}