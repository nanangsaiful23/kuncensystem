<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\OtherTransactionControllerBase;

use App\Models\Journal;

class OtherTransactionController extends Controller
{
    use OtherTransactionControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Transaksi Lain';
        $default['page'] = 'other-transaction';
        $default['section'] = 'all';

        $other_transactions = $this->indexOtherTransactionBase($start_date, $end_date, $pagination);

        return view('admin.layout.page', compact('default', 'other_transactions', 'start_date', 'end_date', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah Transaksi Lain';
        $default['page'] = 'other-transaction';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $journal = $this->storeOtherTransactionBase($request);

        session(['alert' => 'add', 'data' => 'Transaksi Lain']);

        return redirect('/admin/other-transaction/' . $journal->id . '/print');
    }

    public function print($journal_id)
    {
        $role = 'admin';

        $journal = Journal::find($journal_id);

        return view('layout.other-transaction.print', compact('role', 'journal'));
    }
}
