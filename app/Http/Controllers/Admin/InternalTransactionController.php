<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\InternalTransactionControllerBase;

use App\Models\Transaction;

class InternalTransactionController extends Controller
{
    use InternalTransactionControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($role_user, $role_id, $start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar transaksi internal';
        $default['page'] = 'internal-transaction';
        $default['section'] = 'all';

        $all_transactions = $this->indexInternalTransactionBase($role_user, $role_id, $start_date, $end_date, 'all'); 
        $transactions = $this->indexInternalTransactionBase($role_user, $role_id, $start_date, $end_date, $pagination);

        return view('admin.layout.page', compact('default', 'all_transactions', 'transactions', 'role_user', 'role_id', 'start_date', 'end_date', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah transaksi internal';
        $default['page'] = 'internal-transaction';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $transaction = $this->storeInternalTransactionBase('admin', \Auth::user()->id, $request);

        session(['alert' => 'add', 'data' => 'transaksi']);

        return redirect('/admin/internal-transaction/' . $transaction->id . '/print');
    }

    public function detail($transaction_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail transaksi internal';
        $default['page'] = 'internal-transaction';
        $default['section'] = 'detail';

        $transaction = Transaction::find($transaction_id);

        return view('admin.layout.page', compact('default', 'transaction'));
    }

    public function print($transaction_id)
    {
        $role = 'admin';

        $transaction = Transaction::find($transaction_id);

        return view('layout.internal-transaction.print', compact('role', 'transaction'));
    }

    public function edit($transaction_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah transaksi internal';
        $default['page'] = 'internal-transaction';
        $default['section'] = 'edit';

        $transaction = Transaction::find($transaction_id);

        return view('admin.layout.page', compact('default', 'transaction'));
    }

    public function update($transaction_id, Request $request)
    {
        $transaction = $this->updateTransactionBase('admin', \Auth::user()->id, $transaction_id, $request);

        session(['alert' => 'edit', 'data' => 'Data transaksi']);

        return redirect('/admin/transaction/' . $transaction->id . '/detail');
    }

    public function delete($transaction_id)
    {
        $this->reverseTransactionBase('admin', \Auth::user()->id, 'deleted', $transaction_id);

        session(['alert' => 'delete', 'data' => 'Transaksi barang']);

        return redirect('/admin/transaction/all/all/' . date('Y-m-d') . '/' . date('Y-m-d') . '/20');
    }
}
