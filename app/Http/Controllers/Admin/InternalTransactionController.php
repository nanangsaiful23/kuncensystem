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

        $transactions = $this->indexInternalTransactionBase($role_user, $role_id, $start_date, $end_date, $pagination);

        return view('admin.layout.page', compact('default', 'transactions', 'role_user', 'role_id', 'start_date', 'end_date', 'pagination'));
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
}
