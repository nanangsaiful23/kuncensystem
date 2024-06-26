<?php

namespace App\Http\Controllers\Cashier;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\InternalTransactionControllerBase;

use App\Models\Transaction;

class InternalTransactionController extends Controller
{
    use InternalTransactionControllerBase;

    public function __construct()
    {
        $this->middleware('cashier');
    }

    public function index($role_user, $role_id, $start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar transaksi internal';
        $default['page'] = 'internal-transaction';
        $default['section'] = 'all';

        $all_transactions = $this->indexInternalTransactionBase($role_user, $role_id, $start_date, $end_date, 'all'); 
        $transactions = $this->indexInternalTransactionBase($role_user, $role_id, $start_date, $end_date, $pagination);

        return view('cashier.layout.page', compact('default', 'all_transactions', 'transactions', 'role_user', 'role_id', 'start_date', 'end_date', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah transaksi internal';
        $default['page'] = 'internal-transaction';
        $default['section'] = 'create';

        return view('cashier.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $transaction = $this->storeInternalTransactionBase('cashier', \Auth::user()->id, $request);

        session(['alert' => 'add', 'data' => 'transaksi']);

        return redirect('/cashier/internal-transaction/' . $transaction->id . '/print');
    }

    public function detail($transaction_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail transaksi internal';
        $default['page'] = 'internal-transaction';
        $default['section'] = 'detail';

        $transaction = Transaction::find($transaction_id);

        return view('cashier.layout.page', compact('default', 'transaction'));
    }

    public function print($transaction_id)
    {
        $role = 'cashier';

        $transaction = Transaction::find($transaction_id);

        return view('layout.internal-transaction.print', compact('role', 'transaction'));
    }
}
