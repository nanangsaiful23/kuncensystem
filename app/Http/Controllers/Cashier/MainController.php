<?php

namespace App\Http\Controllers\Cashier;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\GoodPrice;
use App\Models\Journal;
use App\Models\Transaction;

class MainController extends Controller
{
    public function __construct()
    {
        $this->middleware('cashier');
    }

    public function index()
    {
        $default['page_name'] = 'Cashier Home';

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

        return view('cashier.index', compact('default', 'transactions', 'other_transactions', 'good_prices'));
    }
}