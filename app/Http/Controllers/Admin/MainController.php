<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Account;
use App\Models\Distributor;
use App\Models\Good;
use App\Models\GoodLoading;
use App\Models\GoodLoadingDetail;
use App\Models\GoodPrice;
use App\Models\Journal;
use App\Models\ReturItem;
use App\Models\ScaleLedger;
use App\Models\Transaction;
use App\Models\TransactionDetail;

class MainController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index()
    {
        $default['page_name'] = 'Admin Home';

        $transactions['cash'] = Transaction::whereDate('transactions.created_at', '=', date('Y-m-d'))
                                            ->where('payment', 'cash')
                                            ->whereRaw('money_paid >= total_sum_price')
                                            ->where('type', 'normal')
                                            ->orderBy('transactions.created_at','desc')
                                            ->get();

        $transactions['credit'] = Transaction::whereDate('transactions.created_at', '=', date('Y-m-d'))
                                            ->where('payment', 'cash')
                                            ->whereRaw('money_paid < total_sum_price')
                                            ->where('type', 'normal')
                                            ->orderBy('transactions.created_at','desc')
                                            ->get();

        $transactions['transfer'] = Transaction::whereDate('transactions.created_at', '=', date('Y-m-d'))
                                            ->where('payment', 'transfer')
                                            ->whereRaw('money_paid >= total_sum_price')
                                            ->where('type', 'normal')
                                            ->orderBy('transactions.created_at','desc')
                                            ->get();

        $transactions['credit_transfer'] = Transaction::whereDate('transactions.created_at', '=', date('Y-m-d'))
                                            ->where('payment', 'transfer')
                                            ->whereRaw('money_paid < total_sum_price')
                                            ->where('type', 'normal')
                                            ->orderBy('transactions.created_at','desc')
                                            ->get();

        $transactions['retur'] = Transaction::whereDate('transactions.created_at', '=', date('Y-m-d'))
                                            ->where('type', 'retur')
                                            ->orderBy('transactions.created_at','desc')
                                            ->get();

        $other_transactions = Journal::where('type', 'like', '%_transaction')
                                    ->whereDate('journals.journal_date', '=', date('Y-m-d')) 
                                    ->get();

        $good_prices = GoodPrice::join('good_units', 'good_units.id', 'good_prices.good_unit_id')
                                ->join('goods', 'goods.id', 'good_units.good_id')
                                ->select('good_prices.*')
                                ->where('good_prices.is_checked', 0)
                                ->where('good_units.deleted_at', null)
                                ->where('goods.deleted_at', null)
                                ->get();

        $cash_account = Account::where('code', '1111')->first();
        $cash_in = Journal::where('debit_account_id', $cash_account->id)->get();
        $cash_out = Journal::where('credit_account_id', $cash_account->id)->get();

        $activa_debits = Journal::select(DB::raw('SUM(journals.debit) as debit'), 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.debit_account_id')
                        ->where('accounts.activa', 'aktiva')
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->orderBy('accounts.code')
                        ->get();

        $activa_credits = Journal::select(DB::raw('SUM(journals.credit) as credit'), 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.credit_account_id')
                        ->where('accounts.activa', 'aktiva')
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->orderBy('accounts.code')
                        ->get();

        $pasiva_debits = Journal::select(DB::raw('SUM(journals.debit) as debit'), 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.debit_account_id')
                        ->where('accounts.activa', 'pasiva')
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->orderBy('accounts.code')
                        ->get();

        $pasiva_credits = Journal::select(DB::raw('SUM(journals.credit) as credit'), 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.credit_account_id')
                        ->where('accounts.activa', 'pasiva')
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->orderBy('accounts.code')
                        ->get();

        #count total neraca dari laba rugi
        [$kas_di_ins, $kas_di_outs] = $this->getPayment('111%');
        [$utang_dagang, $utang_dagang_debit, $utang_dagang_credit] = $this->getPenjualanHpp('2101');
        [$modal_pemilik, $modal_pemilik_debit, $modal_pemilik_credit] = $this->getPenjualanHpp('3001');
        [$penjualan_account, $penjualan_debit, $penjualan_credit] = $this->getPenjualanHpp('4101');
        [$hpp_account, $hpp_debit, $hpp_credit] = $this->getPenjualanHpp('5101');
        [$payment_ins, $payment_outs] = $this->getPayment('52%');
        [$other_debits, $other_credits] = $this->getPayment('610%');

        $total = $penjualan_account->balance - $hpp_account->balance + ($penjualan_credit->sum('credit') - $penjualan_debit->sum('debit')) - ($hpp_debit->sum('debit') - $hpp_credit->sum('credit')) - ($payment_ins->sum('balance') + $payment_ins->sum('debit') - $payment_outs->sum('credit')) - ($other_debits->sum('balance') + $other_debits->sum('debit') - $other_credits->sum('credit'));

        $total = $total - ($activa_debits->sum('balance') + $activa_debits->sum('debit') - $activa_credits->sum('credit')) + $utang_dagang->balance - ($utang_dagang->balance + $utang_dagang_debit->sum('debit') - $utang_dagang_credit->sum('credit')) + $modal_pemilik->balance - ($modal_pemilik->balance + $modal_pemilik_debit->sum('debit') - $modal_pemilik_credit->sum('credit')) + $kas_di_ins->sum('balance');

        $yesterday = Carbon::yesterday();
        $last_cash_flow = Journal::where('type', 'cash_draw')->whereDate('journal_date', $yesterday)->first();

        return view('admin.index', compact('default', 'transactions', 'other_transactions', 'good_prices', 'cash_account', 'cash_in', 'cash_out', 'total', 'last_cash_flow'));
    }

    public function getPenjualanHpp($code)
    {
        $account = Account::where('code', $code)->first();
        $debit = Journal::where('debit_account_id', $account->id)
                                  ->get();
        $credit = Journal::where('credit_account_id', $account->id)
                                   ->get();

        return [$account, $debit, $credit];
    }

    public function getPayment($code)
    {
        $debit = Journal::select(DB::raw('SUM(journals.debit) as debit'), 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.debit_account_id')
                        ->where('accounts.code', 'like', $code)
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->orderBy('accounts.code')
                        ->get();

        $credit = Journal::select(DB::raw('SUM(journals.credit) as credit'), 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.credit_account_id')
                        ->where('accounts.code', 'like', $code)
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->orderBy('accounts.code')
                        ->get();

        return [$debit, $credit];
    }

    public function profit()
    {
        $default['page_name'] = 'Laba Rugi';

        [$penjualan_account, $penjualan_debit, $penjualan_credit] = $this->getPenjualanHpp('4101');
        [$hpp_account, $hpp_debit, $hpp_credit] = $this->getPenjualanHpp('5101');
        [$payment_ins, $payment_outs] = $this->getPayment('52%');
        [$other_debits, $other_credits] = $this->getPayment('610%');

        return view('admin.profit', compact('default', 'penjualan_account', 'penjualan_debit', 'penjualan_credit', 'hpp_account', 'hpp_debit', 'hpp_credit', 'payment_ins', 'payment_outs', 'other_debits', 'other_credits'));
    }

    public function scale($start_date, $end_date)
    {
        $default['page_name'] = 'Neraca';

        $activa_debits = Journal::select(DB::raw('SUM(journals.debit) as debit'), 'accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.debit_account_id')
                        ->where('accounts.activa', 'aktiva')
                        ->where('accounts.deleted_at', null)
                        // ->whereDate('journals.journal_date', '>=', $start_date) 
                        // ->whereDate('journals.journal_date', '<=', $end_date) 
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->orderBy('accounts.code')
                        ->get();

        $activa_credits = Journal::select(DB::raw('SUM(journals.credit) as credit'), 'accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.credit_account_id')
                        ->where('accounts.activa', 'aktiva')
                        ->where('accounts.deleted_at', null)
                        // ->whereDate('journals.journal_date', '>=', $start_date) 
                        // ->whereDate('journals.journal_date', '<=', $end_date) 
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->orderBy('accounts.code')
                        ->get();

        $pasiva_debits = Journal::select(DB::raw('SUM(journals.debit) as debit'), 'accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.debit_account_id')
                        ->where('accounts.activa', 'pasiva')
                        ->where('accounts.deleted_at', null)
                        // ->whereDate('journals.journal_date', '>=', $start_date) 
                        // ->whereDate('journals.journal_date', '<=', $end_date) 
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->orderBy('accounts.code')
                        ->get();

        $pasiva_credits = Journal::select(DB::raw('SUM(journals.credit) as credit'), 'accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.credit_account_id')
                        ->where('accounts.activa', 'pasiva')
                        ->where('accounts.deleted_at', null)
                        // ->whereDate('journals.journal_date', '>=', $start_date) 
                        // ->whereDate('journals.journal_date', '<=', $end_date) 
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->orderBy('accounts.code')
                        ->get();

        #count total neraca dari laba rugi
        [$kas_di_ins, $kas_di_outs] = $this->getPayment('111%');
        [$utang_dagang, $utang_dagang_debit, $utang_dagang_credit] = $this->getPenjualanHpp('2101');
        [$modal_pemilik, $modal_pemilik_debit, $modal_pemilik_credit] = $this->getPenjualanHpp('3001');
        [$penjualan_account, $penjualan_debit, $penjualan_credit] = $this->getPenjualanHpp('4101');
        [$hpp_account, $hpp_debit, $hpp_credit] = $this->getPenjualanHpp('5101');
        [$payment_ins, $payment_outs] = $this->getPayment('52%');
        [$other_debits, $other_credits] = $this->getPayment('610%');

        $total = $penjualan_account->balance - $hpp_account->balance + ($penjualan_credit->sum('credit') - $penjualan_debit->sum('debit')) - ($hpp_debit->sum('debit') - $hpp_credit->sum('credit')) - ($payment_ins->sum('balance') + $payment_ins->sum('debit') - $payment_outs->sum('credit')) - ($other_debits->sum('balance') + $other_debits->sum('debit') - $other_credits->sum('credit'));

        $total = $total - ($activa_debits->sum('balance') + $activa_debits->sum('debit') - $activa_credits->sum('credit')) + $utang_dagang->balance - ($utang_dagang->balance + $utang_dagang_debit->sum('debit') - $utang_dagang_credit->sum('credit')) + $modal_pemilik->balance - ($modal_pemilik->balance + $modal_pemilik_debit->sum('debit') - $modal_pemilik_credit->sum('credit')) + $kas_di_ins->sum('balance');

        $laba[0] = ($penjualan_account->balance - $hpp_account->balance) - $payment_ins->sum('balance') + (2 * $other_debits[0]->balance) - $other_debits->sum('balance');
        $laba[1] = ($penjualan_credit->sum('credit') - $penjualan_debit->sum('debit')) - ($hpp_debit->sum('debit') - $hpp_credit->sum('credit')) - ($payment_ins->sum('debit') - $payment_outs->sum('credit')) - ($other_debits->sum('debit') - $other_credits->sum('credit'));
        $laba[2] = ($penjualan_account->balance + $hpp_account->balance + ($penjualan_credit->sum('credit') - $penjualan_debit->sum('debit')) - ($hpp_debit->sum('debit') - $hpp_credit->sum('credit')) - ($payment_ins->sum('balance') + $payment_ins->sum('debit') - $payment_outs->sum('credit'))) - ($other_debits->sum('balance') + $other_debits->sum('debit') - $other_credits->sum('credit'));

        return view('admin.scale', compact('default', 'activa_debits', 'activa_credits', 'pasiva_debits', 'pasiva_credits', 'total', 'laba', 'start_date', 'end_date'));
    }

    public function cashFlow($start_date, $end_date, $pagination)
    {
        $default['page_name'] = 'Riwayat Pengambilan Kas di Tangan';

        if($pagination == 'all')
        {
            $journals = Journal::where('type', 'cash_draw')
                               ->whereDate('journal_date', '>=', $start_date)
                                ->whereDate('journal_date', '<=', $end_date) 
                                ->orderBy('journal_date', 'desc')
                                ->get();
        }
        else
        {
            $journals = Journal::where('type', 'cash_draw')
                               ->whereDate('journal_date', '>=', $start_date)
                                ->whereDate('journal_date', '<=', $end_date) 
                                ->orderBy('journal_date', 'desc')
                                ->paginate($pagination);
        }

        return view('admin.cash-flow', compact('default', 'journals', 'start_date', 'end_date', 'pagination'));
    }

    public function storeScaleLedger($start_date, $end_date, Request $request)
    {
        DB::statement("UPDATE distributors 
            INNER JOIN (SELECT distributors.id as dist_id, COALESCE(SUM(aset.total), 0) as total
            FROM distributors
            LEFT JOIN (
                SELECT goods.id, goods.last_distributor_id, SUM(goods.last_stock * good_units.buy_price) AS total
                FROM goods
                LEFT JOIN good_units ON good_units.id = goods.base_unit_id 
                WHERE good_units.deleted_at IS NULL AND goods.deleted_at IS NULL
                GROUP BY goods.id, goods.last_distributor_id) AS aset ON aset.last_distributor_id = distributors.id
                GROUP BY distributors.id) AS total_aset ON total_aset.dist_id = distributors.id
            SET distributors.total_aset = total_aset.total
            WHERE distributors.id = total_aset.dist_id");

         DB::statement("UPDATE distributors 
            INNER JOIN ( 
                SELECT distributors.id, COALESCE(SUM((transaction_details.selling_price - transaction_details.buy_price) * transaction_details.quantity), 0) AS total 
                FROM transaction_details 
                JOIN transactions ON transactions.id = transaction_details.transaction_id 
                JOIN good_units ON transaction_details.good_unit_id = good_units.id 
                JOIN goods ON goods.id = good_units.good_id 
                JOIN distributors ON distributors.id = goods.last_distributor_id 
                WHERE transaction_details.type = 'normal' AND transactions.deleted_at IS NULL AND goods.deleted_at IS NULL 
                GROUP BY distributors.id) as untung ON untung.id = distributors.id 
            SET distributors.total_profit = COALESCE(untung.total, 0) 
            WHERE distributors.id = untung.id;");

         DB::statement("UPDATE distributors 
            INNER JOIN ( 
                SELECT distributors.id, COALESCE(SUM(transaction_details.buy_price * transaction_details.quantity), 0) AS total 
                FROM transaction_details 
                JOIN transactions ON transactions.id = transaction_details.transaction_id 
                JOIN good_units ON transaction_details.good_unit_id = good_units.id 
                JOIN goods ON goods.id = good_units.good_id 
                JOIN distributors ON distributors.id = goods.last_distributor_id 
                WHERE (transaction_details.type = '5215' OR transaction_details.type = 'stock_opname') AND transactions.deleted_at IS NULL AND goods.deleted_at IS NULL 
                GROUP BY distributors.id) as rugi ON rugi.id = distributors.id 
            SET distributors.total_rugi = COALESCE(rugi.total, 0) 
            WHERE distributors.id = rugi.id;");

        $data = $request->input();

        $data_ledger['start_date'] = $start_date;
        $data_ledger['end_date']   = $end_date;

        for($i = 0; $i < sizeof($data['account_ids']); $i++)
        {
            $data_ledger['account_id'] = $data['account_ids'][$i];
            $data_ledger['initial']    = $data['initials'][$i];
            $data_ledger['ongoing']    = $data['ongoings'][$i];
            $data_ledger['current']    = $data['currents'][$i];

            ScaleLedger::create($data_ledger);
        }

        return redirect('/admin/scale/' . $start_date . '/' . $end_date);
    }

    public function scaleLedger($start_date, $end_date)
    {
        $default['page_name'] = 'Grafik Ledger Neraca';
        
        $dates = $this->getScaleLedger($start_date, $end_date, '21,22,24,31,35,41,42,43');

        return view('admin.scale-ledger', compact('default', 'dates', 'start_date', 'end_date'));
    }

    public function getScaleLedger($start_date, $end_date, $params)
    {
        if($params == 'profit')
            $params = '21,22,24,31,35,41,42,43';

        $data_params = explode(',', $params);

        $dates = Journal::select(DB::raw('DISTINCT YEAR(journals.journal_date) as year, MONTH(journals.journal_date) as month'))
                        ->whereDate('journals.journal_date', '>=', $start_date)
                        ->whereDate('journals.journal_date', '<=', $end_date)
                        ->groupBy(DB::raw('YEAR(journals.journal_date)'))
                        ->groupBy(DB::raw('MONTH(journals.journal_date)'))
                        ->orderBy('journals.journal_date', 'desc')
                        ->paginate(20);

        foreach($dates as $date)
        {
            $date->data = Journal::rightJoin('accounts', 'accounts.id', 'journals.debit_account_id')
                                ->select('accounts.code', 'accounts.color', DB::raw('COALESCE(SUM(journals.debit), 0) as debit'), 'accounts.name')
                                ->whereYear('journals.journal_date', $date->year)
                                ->whereMonth('journals.journal_date', $date->month)
                                ->whereIn('accounts.id', $data_params)
                                ->groupBy('accounts.code')
                                ->groupBy('accounts.color')
                                ->groupBy('accounts.name')
                                ->orderBy(DB::raw('SUM(journals.debit)'), 'asc')
                                ->get();

            $date->dataplus = Journal::rightJoin('accounts', 'accounts.id', 'journals.credit_account_id')
                                ->select('accounts.code', 'accounts.color', DB::raw('COALESCE(SUM(journals.debit), 0) as debit'), 'accounts.name')
                                ->whereYear('journals.journal_date', $date->year)
                                ->whereMonth('journals.journal_date', $date->month)
                                ->whereIn('accounts.id', ['20'])
                                ->groupBy('accounts.code')
                                ->groupBy('accounts.color')
                                ->groupBy('accounts.name')
                                ->get();

            $date->profit = $date->dataplus->sum('debit') - $date->data->sum('debit');

            $date->date = $date->year . '-' . $date->month;
        }

        // dd($dates[0]);die;

        return $dates;
    }

    function salesGraph($type, $start_date, $end_date)
    {
        $default['page_name'] = 'Grafik Penjualan ' . $type;
        
        $result = $this->getSalesGraph($type, $start_date, $end_date);

        return view('admin.sales-graph-' . $type, compact('default', 'result', 'start_date', 'end_date'));
    }

    function getSalesGraph($type, $start_date, $end_date)
    {
        if($type == 'category')
            $result = TransactionDetail::leftJoin('good_units', 'good_units.id', 'transaction_details.good_unit_id')
                                    ->leftJoin('goods', 'goods.id', 'good_units.good_id')
                                    ->leftJoin('categories', 'goods.category_id', 'categories.id')
                                    ->select('categories.name', 'categories.color', DB::raw('COALESCE(SUM(transaction_details.real_quantity), 0) as qty, COALESCE(SUM(transaction_details.sum_price), 0) as total_price, COALESCE(SUM((transaction_details.selling_price - transaction_details.buy_price) * transaction_details.quantity), 0) as profit'))
                                    ->whereDate('transaction_details.created_at', '>=', $start_date)
                                    ->whereDate('transaction_details.created_at', '<=', $end_date)
                                    ->groupBy('categories.name')
                                    ->groupBy('categories.color')
                                    ->get();
        elseif($type == 'month')
            $result = Transaction::select(DB::raw('DISTINCT YEAR(transactions.created_at) as year, MONTH(transactions.created_at) as month, COALESCE(SUM(transactions.total_sum_price), 0) as total'))
                                 ->whereDate('transactions.created_at', '>=', $start_date)
                                ->whereDate('transactions.created_at', '<=', $end_date)
                                ->groupBy(DB::raw('YEAR(transactions.created_at)'))
                                ->groupBy(DB::raw('MONTH(transactions.created_at)'))
                                ->paginate(20);
        return $result;
    }
}
