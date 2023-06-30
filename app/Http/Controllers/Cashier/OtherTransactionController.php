<?php

namespace App\Http\Controllers\Cashier;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\OtherTransactionControllerBase;

use App\Models\OtherTransaction;

class OtherTransactionController extends Controller
{
    use OtherTransactionControllerBase;

    public function __construct()
    {
        $this->middleware('cashier');
    }

    public function index($start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Transaksi Lain';
        $default['page'] = 'other-transaction';
        $default['section'] = 'all';

        $other_transactions = $this->indexOtherTransactionBase($start_date, $end_date, $pagination);

        return view('cashier.layout.page', compact('default', 'other_transactions', 'start_date', 'end_date', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah Transaksi Lain';
        $default['page'] = 'other-transaction';
        $default['section'] = 'create';

        return view('cashier.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $this->storeOtherTransactionBase($request);

        session(['alert' => 'add', 'data' => 'Transaksi Lain']);

        return redirect('/cashier/other-transaction/' . date('Y-m-d') . '/' . date('Y-m-d') . '/15');
    }

    public function print($journal_id)
    {
        $role = 'cashier';

        $journal = Journal::find($journal_id);

        return view('layout.other-transaction.print', compact('role', 'journal'));
    }
}
