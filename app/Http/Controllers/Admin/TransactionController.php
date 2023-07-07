<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\TransactionControllerBase;

use App\Models\Transaction;

class TransactionController extends Controller
{
    use TransactionControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($role_user, $role_id, $start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar transaksi';
        $default['page'] = 'transaction';
        $default['section'] = 'all';

        $transactions = $this->indexTransactionBase($role_user, $role_id, $start_date, $end_date, $pagination);

        return view('admin.layout.page', compact('default', 'transactions', 'role_user', 'role_id', 'start_date', 'end_date', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah transaksi';
        $default['page'] = 'transaction';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $transaction = $this->storeTransactionBase('admin', \Auth::user()->id, $request);

        session(['alert' => 'add', 'data' => 'transaksi']);

        return redirect('/admin/transaction/' . $transaction->id . '/print');
    }

    public function detail($transaction_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail transaksi';
        $default['page'] = 'transaction';
        $default['section'] = 'detail';

        $transaction = Transaction::find($transaction_id);

        return view('admin.layout.page', compact('default', 'transaction'));
    }

    public function print($transaction_id)
    {
        $role = 'admin';

        $transaction = Transaction::find($transaction_id);

        return view('layout.transaction.print', compact('role', 'transaction'));
    }

    public function reverse($transaction_id)
    {
        $this->reverseTransactionBase('admin', \Auth::user()->id, $transaction_id);

        session(['alert' => 'add', 'data' => 'transaksi']);

        return redirect('/admin/transaction/all/all/' . date('Y-m-d') . '/' . date('Y-m-d') . '/20');
    }

    public function resume($category_id, $distributor_id, $start_date, $end_date)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Resume transaksi';
        $default['page'] = 'transaction';
        $default['section'] = 'resume';

        $transaction_details = $this->resumeTransactionBase($category_id, $distributor_id, $start_date, $end_date);

        return view('admin.layout.page', compact('default', 'transaction_details', 'category_id', 'distributor_id', 'start_date', 'end_date'));
    }
}
