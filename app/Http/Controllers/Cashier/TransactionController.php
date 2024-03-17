<?php

namespace App\Http\Controllers\Cashier;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\TransactionControllerBase;

use App\Models\Transaction;

class TransactionController extends Controller
{
    use TransactionControllerBase;

    public function __construct()
    {
        $this->middleware('cashier');
    }

    public function index($role_user, $role_id, $start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar transaksi';
        $default['page'] = 'transaction';
        $default['section'] = 'all';

        $role_user = 'cashier';
        $role_id = \Auth::user()->id;

        [$transactions, $all_normal, $all_retur, $hpp_normal, $hpp_retur, $hpp_retur_normal] = $this->indexTransactionBase($role_user, $role_id, $start_date, $end_date, $pagination);

        return view('cashier.layout.page', compact('default', 'transactions', 'all_normal', 'all_retur', 'hpp_normal', 'hpp_retur', 'hpp_retur_normal', 'role_user', 'role_id', 'start_date', 'end_date', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah transaksi';
        $default['page'] = 'transaction';
        $default['section'] = 'create';

        return view('cashier.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $transaction = $this->storeTransactionBase('cashier', \Auth::user()->id, $request);

        session(['alert' => 'add', 'data' => 'transaksi']);

        return redirect('/cashier/transaction/' . $transaction->id . '/print');
    }

    public function detail($transaction_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail transaksi';
        $default['page'] = 'transaction';
        $default['section'] = 'detail';

        $transaction = Transaction::find($transaction_id);

        return view('cashier.layout.page', compact('default', 'transaction'));
    }

    public function print($transaction_id)
    {
        $role = 'cashier';

        $transaction = Transaction::find($transaction_id);

        return view('layout.transaction.print', compact('role', 'transaction'));
    }

    public function resumeTotal($start_date, $end_date)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Resume transaksi total';
        $default['page'] = 'transaction';
        $default['section'] = 'resume-total';

        $transactions = $this->resumeTotalTransactionBase($start_date, $end_date);

        return view('cashier.layout.page', compact('default', 'transactions', 'start_date', 'end_date'));
    }

    public function storeMoney(Request $request)
    {
        $this->storeMoneyTransactionBase($request);

        session(['alert' => 'add', 'data' => 'pengambilan uang']);

        return redirect('/cashier/transaction/resumeTotal/' . date('Y-m-d') . '/' . date('Y-m-d'));
    }
}
