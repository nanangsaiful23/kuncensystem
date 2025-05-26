<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\GoodControllerBase;
use App\Http\Controllers\Base\TransactionControllerBase;

use App\Models\Transaction;

class TransactionController extends Controller
{
    use GoodControllerBase;
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

        // $role_user = 'admin';
        // $role_id = \Auth::user()->id;

        [$transactions, $all_normal, $all_retur, $hpp_normal, $hpp_retur, $hpp_retur_normal] = $this->indexTransactionBase($role_user, $role_id, $start_date, $end_date, $pagination);

        return view('admin.layout.page', compact('default', 'transactions', 'all_normal', 'all_retur', 'hpp_normal', 'hpp_retur', 'hpp_retur_normal', 'role_user', 'role_id', 'start_date', 'end_date', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah transaksi';
        $default['page'] = 'transaction';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default'));
    }

    public function createTouch()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah transaksi';
        $default['page'] = 'transaction';
        $default['section'] = 'create-touch';

        return view('admin.layout.page', compact('default'));
    }

    public function createNew()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah transaksi';
        $default['page'] = 'transaction';
        $default['section'] = 'create-new';

        $role = 'admin';

        $goods = $this->getPopularGoodsGoodBase('10', '20');
        // dd($goods);die;

        return view('layout.transaction.create-new', compact('default', 'goods' ,'role'));
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
        $this->reverseTransactionBase('admin', \Auth::user()->id, 'not valid', $transaction_id);

        session(['alert' => 'add', 'data' => 'transaksi']);

        return redirect('/admin/transaction/all/all/' . date('Y-m-d') . '/' . date('Y-m-d') . '/20');
    }

    public function resume($type, $category_id, $distributor_id, $start_date, $end_date)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Resume transaksi';
        $default['page'] = 'transaction';
        $default['section'] = 'resume';

        [$transaction_details, $total] = $this->resumeTransactionBase($type, $category_id, $distributor_id, $start_date, $end_date);

        return view('admin.layout.page', compact('default', 'transaction_details', 'total', 'type', 'category_id', 'distributor_id', 'start_date', 'end_date'));
    }

    public function resumeTotal($start_date, $end_date)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Resume transaksi total';
        $default['page'] = 'transaction';
        $default['section'] = 'resume-total';

        $transactions = $this->resumeTotalTransactionBase($start_date, $end_date);

        return view('admin.layout.page', compact('default', 'transactions', 'start_date', 'end_date'));
    }

    public function storeMoney(Request $request)
    {
        $result = $this->storeMoneyTransactionBase($request);

        return response()->json([
            "result"  => $result
        ], 200);
    }

    public function edit($transaction_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah transaksi';
        $default['page'] = 'transaction';
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
