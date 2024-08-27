<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Account;
use App\Models\GoodLoading;
use App\Models\GoodLoadingDetail;
use App\Models\GoodPrice;
use App\Models\Journal;
use App\Models\ReturItem;
use App\Models\Transaction;

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
        [$pendapatan_lain, $pendapatan_lain_debit, $pendapatan_lain_credit] = $this->getPenjualanHpp('6101');
        [$biaya_lain, $biaya_lain_debit, $biaya_lain_credit] = $this->getPenjualanHpp('6102');

        $total = $penjualan_account->balance - $hpp_account->balance + ($penjualan_credit->sum('credit') - $penjualan_debit->sum('debit')) - ($hpp_debit->sum('debit') - $hpp_credit->sum('credit')) - ($payment_ins->sum('balance') + $payment_ins->sum('debit') - $payment_outs->sum('credit')) - ($pendapatan_lain->balance + $pendapatan_lain_debit->sum('debit') - $pendapatan_lain_credit->sum('credit')) - ($biaya_lain->balance + $biaya_lain_debit->sum('debit') - $biaya_lain_credit->sum('credit'));

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
        [$other_income_debits, $other_income_credits] = $this->getPayment('6101');
        [$other_outcome_debits, $other_outcome_credits] = $this->getPayment('6102');

        return view('admin.profit', compact('default', 'penjualan_account', 'penjualan_debit', 'penjualan_credit', 'hpp_account', 'hpp_debit', 'hpp_credit', 'payment_ins', 'payment_outs', 'other_income_debits', 'other_income_credits', 'other_outcome_debits', 'other_outcome_credits'));
    }

    public function scale($start_date, $end_date)
    {
        $default['page_name'] = 'Neraca';

        $activa_debits = Journal::select(DB::raw('SUM(journals.debit) as debit'), 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.debit_account_id')
                        ->where('accounts.activa', 'aktiva')
                        ->where('accounts.deleted_at', null)
                        ->whereDate('journals.journal_date', '>=', $start_date) 
                        ->whereDate('journals.journal_date', '<=', $end_date) 
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->orderBy('accounts.code')
                        ->get();

        $activa_credits = Journal::select(DB::raw('SUM(journals.credit) as credit'), 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.credit_account_id')
                        ->where('accounts.activa', 'aktiva')
                        ->where('accounts.deleted_at', null)
                        ->whereDate('journals.journal_date', '>=', $start_date) 
                        ->whereDate('journals.journal_date', '<=', $end_date) 
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->orderBy('accounts.code')
                        ->get();

        $pasiva_debits = Journal::select(DB::raw('SUM(journals.debit) as debit'), 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.debit_account_id')
                        ->where('accounts.activa', 'pasiva')
                        ->where('accounts.deleted_at', null)
                        ->whereDate('journals.journal_date', '>=', $start_date) 
                        ->whereDate('journals.journal_date', '<=', $end_date) 
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->orderBy('accounts.code')
                        ->get();

        $pasiva_credits = Journal::select(DB::raw('SUM(journals.credit) as credit'), 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.credit_account_id')
                        ->where('accounts.activa', 'pasiva')
                        ->where('accounts.deleted_at', null)
                        ->whereDate('journals.journal_date', '>=', $start_date) 
                        ->whereDate('journals.journal_date', '<=', $end_date) 
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
        [$pendapatan_lain, $pendapatan_lain_debit, $pendapatan_lain_credit] = $this->getPenjualanHpp('6101');
        [$biaya_lain, $biaya_lain_debit, $biaya_lain_credit] = $this->getPenjualanHpp('6102');

        $total = $penjualan_account->balance - $hpp_account->balance + ($penjualan_credit->sum('credit') - $penjualan_debit->sum('debit')) - ($hpp_debit->sum('debit') - $hpp_credit->sum('credit')) - ($payment_ins->sum('balance') + $payment_ins->sum('debit') - $payment_outs->sum('credit')) - ($pendapatan_lain->balance + $pendapatan_lain_debit->sum('debit') - $pendapatan_lain_credit->sum('credit')) - ($biaya_lain->balance + $biaya_lain_debit->sum('debit') - $biaya_lain_credit->sum('credit'));

        $total = $total - ($activa_debits->sum('balance') + $activa_debits->sum('debit') - $activa_credits->sum('credit')) + $utang_dagang->balance - ($utang_dagang->balance + $utang_dagang_debit->sum('debit') - $utang_dagang_credit->sum('credit')) + $modal_pemilik->balance - ($modal_pemilik->balance + $modal_pemilik_debit->sum('debit') - $modal_pemilik_credit->sum('credit')) + $kas_di_ins->sum('balance');

        // $real_stock = DB::select(DB::raw("SELECT SUM(final.stock) as stock
        //                                 FROM (SELECT goods.id, total.total_loading, total.total_transaction, total.total_real, total.buy_price, total.quantity, total.real_price, SUM(total.total_real * total.real_price) as stock
        //                                         FROM goods 
        //                                         JOIN (SELECT goods.id, coalesce(loading.total_loading, 0), coalesce(transaction.total_transaction, 0), SUM(loading.total_loading - transaction.total_transaction) as total_real, price.buy_price, price.quantity, SUM(price.buy_price/price.quantity) as real_price
        //                                             FROM goods
        //                                             LEFT JOIN (SELECT goods.id, SUM(good_loading_details.real_quantity) AS total_loading
        //                                                 FROM good_loading_details
        //                                                 JOIN good_units ON good_units.id = good_loading_details.good_unit_id
        //                                                 JOIN goods ON goods.id = good_units.good_id
        //                                                 WHERE good_loading_details.deleted_at IS NULL AND goods.deleted_at IS NULL AND good_units.deleted_at IS NULL
        //                                                 GROUP BY goods.id) as loading ON loading.id = goods.id
        //                                             LEFT JOIN (SELECT goods.id, SUM(transaction_details.real_quantity) AS total_transaction
        //                                                 FROM transaction_details
        //                                                 JOIN good_units ON good_units.id = transaction_details.good_unit_id
        //                                                 RIGHT JOIN goods ON goods.id = good_units.good_id
        //                                                 WHERE transaction_details.deleted_at IS NULL AND goods.deleted_at IS NULL AND good_units.deleted_at IS NULL
        //                                                 GROUP BY goods.id) as transaction ON transaction.id = goods.id
        //                                             LEFT JOIN (SELECT goods.id, good_units.buy_price, units.quantity
        //                                                 FROM good_units
        //                                                 RIGHT JOIN goods ON goods.id = good_units.good_id
        //                                                 JOIN units ON units.id = good_units.unit_id
        //                                                 GROUP BY goods.id, good_units.buy_price, units.quantity) as price ON price.id = goods.id
        //                                         GROUP BY goods.id) as total ON total.id = goods.id
        //                                 GROUP BY goods.id) as final"));

        return view('admin.scale', compact('default', 'activa_debits', 'activa_credits', 'pasiva_debits', 'pasiva_credits', 'total', 'start_date', 'end_date'));
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
}