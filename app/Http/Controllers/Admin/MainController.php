<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

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

        return view('admin.index', compact('default', 'transactions', 'other_transactions', 'good_prices', 'cash_account', 'cash_in', 'cash_out'));
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

    public function scale()
    {
        $default['page_name'] = 'Neraca';

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
        [$penjualan_account, $penjualan_debit, $penjualan_credit] = $this->getPenjualanHpp('4101');
        [$hpp_account, $hpp_debit, $hpp_credit] = $this->getPenjualanHpp('5101');
        [$payment_ins, $payment_outs] = $this->getPayment('52%');
        [$utang_dagang, $utang_dagang_debit, $utang_dagang_credit] = $this->getPenjualanHpp('4101');
        [$modal_pemilik, $modal_pemilik_debit, $modal_pemilik_credit] = $this->getPenjualanHpp('3001');
        [$kas_ditangan, $kas_ditangan_debit, $kas_ditangan_credit] = $this->getPenjualanHpp('1111');

        $total = $penjualan_account->balance + $hpp_account->balance + ($penjualan_credit->sum('credit') - $penjualan_debit->sum('debit')) - ($hpp_debit->sum('debit') - $hpp_credit->sum('credit')) - ($payment_ins->sum('balance') + $payment_ins->sum('debit') - $payment_outs->sum('credit'));

        $total += ($activa_debits->sum('balance') + $activa_debits->sum('debit') - $activa_credits->sum('credit')) - ($utang_dagang->sum('balance') + $utang_dagang_debit->sum('debit') - $utang_dagang_credit->sum('credit')) + $modal_pemilik->sum('balance') + $kas_ditangan->sum('balance');

        return view('admin.scale', compact('default', 'activa_debits', 'activa_credits', 'pasiva_debits', 'pasiva_credits', 'total'));
    }

    public function retur($distributor_id, $status, $pagination)
    {
        $default['page_name'] = 'Barang retur';
        if($status == 'null') $status = null;

        if($distributor_id == 'all')
        {
            if($pagination == 'all')
            {
                $items = ReturItem::where('returned_type', $status)->get();
            }
            else
            {
                $items = ReturItem::where('returned_type', $status)->paginate($pagination);
            }
        }
        else
        {
            if($pagination == 'all')
            {
                $items = ReturItem::where('last_distributor_id', $distributor_id)
                                  ->where('returned_type', $status)
                                  ->get();
            }
            else
            {
                $items = ReturItem::where('last_distributor_id', $distributor_id)
                                  ->where('returned_type', $status)
                                  ->paginate($pagination);
            }
        }

        return view('admin.retur', compact('default', 'items', 'distributor_id', 'status', 'pagination'));
    }

    public function returItem($item_id, Request $request)
    {
        $data_item['returned_date'] = date('Y-m-d');
        $data_item['returned_type'] = $request->type;

        $item = ReturItem::find($item_id);
        $item->update($data_item);

        if($request->type == 'uang')
        {
            $data_journal['type']               = 'retur';
            $data_journal['journal_date']       = date('Y-m-d');
            $data_journal['name']               = 'Retur barang ' . $item->good->name . ' dengan uang tanggal ' . displayDate(date('Y-m-d'));
            $data_journal['debit_account_id']   = Account::where('code', '1111')->first()->id;
            $data_journal['debit']              = $item->good->getPcsSellingPrice()->buy_price;
            $data_journal['credit_account_id']  = Account::where('code', '1141')->first()->id;
            $data_journal['credit']             = $item->good->getPcsSellingPrice()->buy_price;

            Journal::create($data_journal);
        }
        else
        {
            $data_loading['role']         = 'admin';
            $data_loading['role_id']      = \Auth::user()->id;
            $data_loading['checker']      = 'Load by sistem';
            $data_loading['loading_date'] = date('Y-m-d');
            $data_loading['distributor_id']   = $item->good->getLastBuy()->good_loading->distributor->id;
            $data_loading['total_item_price'] = unformatNumber($item->good->getPcsSellingPrice()->buy_price);
            $data_loading['note']             = 'Loading barang retur (berupa barang)';
            $data_loading['payment']          = 'cash';

            $good_loading = GoodLoading::create($data_loading);

            $data_detail['good_loading_id'] = $good_loading->id;
            $data_detail['good_unit_id']    = $item->good->getPcsSellingPrice()->id;
            $data_detail['last_stock']      = $item->good->getStock();
            $data_detail['quantity']        = 1;
            $data_detail['real_quantity']   = 1;
            $data_detail['price']           = unformatNumber($item->good->getPcsSellingPrice()->buy_price);
            $data_detail['selling_price']   = unformatNumber($item->good->getPcsSellingPrice()->selling_price);
            $data_detail['expiry_date']     = null;

            GoodLoadingDetail::create($data_detail);

            $account = Account::where('code', '1111')->first();

            $data_journal_loading_retur['type']               = 'good_loading';
            $data_journal_loading_retur['journal_date']       = date('Y-m-d');
            $data_journal_loading_retur['name']               = 'Loading barang retur (dari distributor berupa barang) tanggal ' . displayDate(date('Y-m-d'));
            $data_journal_loading_retur['debit_account_id']   = Account::where('code', '1141')->first()->id;
            $data_journal_loading_retur['debit']              = unformatNumber($data_loading['total_item_price']);
            $data_journal_loading_retur['credit_account_id']  = $account->id;
            $data_journal_loading_retur['credit']             = unformatNumber($data_loading['total_item_price']);

            Journal::create($data_journal_loading_retur);
        }

        session(['alert' => 'add', 'data' => 'retur']);

        return redirect('/admin/retur/all/null/20');
    }
}