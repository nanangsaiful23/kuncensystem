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

        return view('admin.index', compact('default', 'transactions', 'other_transactions', 'good_prices'));
    }

    public function profit()
    {
        $default['page_name'] = 'Laba Rugi';

        $penjualan_account = Account::where('code', '4101')->first();
        $penjualan = Journal::where('credit_account_id', $penjualan_account->id)
                            ->get();

        $hpp_account = Account::where('code', '5101')->first();
        $hpp = Journal::where('debit_account_id', $hpp_account->id)
                      ->get();

        $payment_ins = Journal::select(DB::raw('SUM(journals.debit) as debit'), 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.debit_account_id')
                        ->where('accounts.code', 'like', '52%')
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->get();

        $payment_outs = Journal::select(DB::raw('SUM(journals.credit) as credit'), 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.credit_account_id')
                        ->where('accounts.code', 'like', '52%')
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->get();

        $other_incomes = Journal::select(DB::raw('SUM(journals.credit) as credit'), 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.credit_account_id')
                        ->where('accounts.code', '6101')
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->get();

                        // dd($other_incomes);die;

        $other_outcomes = Journal::select(DB::raw('SUM(journals.debit) as debit'), 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.debit_account_id')
                        ->where('accounts.code', '6102')
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->get();

        return view('admin.profit', compact('default', 'penjualan_account', 'penjualan', 'hpp_account', 'hpp', 'payment_ins', 'payment_outs', 'other_incomes', 'other_outcomes'));
    }

    public function scale()
    {
        $default['page_name'] = 'Neraca';

        $activa_debits = Journal::select(DB::raw('SUM(journals.debit) as debit'), 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.debit_account_id')
                        ->where('accounts.activa', 'aktiva')
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->get();

        $activa_credits = Journal::select(DB::raw('SUM(journals.credit) as credit'), 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.credit_account_id')
                        ->where('accounts.activa', 'aktiva')
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->get();

        $pasiva_debits = Journal::select(DB::raw('SUM(journals.debit) as debit'), 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.debit_account_id')
                        ->where('accounts.activa', 'pasiva')
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->get();

        $pasiva_credits = Journal::select(DB::raw('SUM(journals.credit) as credit'), 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.credit_account_id')
                        ->where('accounts.activa', 'pasiva')
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->get();

        return view('admin.scale', compact('default', 'activa_debits', 'activa_credits', 'pasiva_debits', 'pasiva_credits'));
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