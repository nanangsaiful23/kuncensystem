<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\JournalControllerBase;

use App\Models\Journal;

class JournalController extends Controller
{
    use JournalControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($code, $type, $start_date, $end_date, $sort, $order, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Jurnal';
        $default['page'] = 'journal';
        $default['section'] = 'all';

        $journals = $this->indexJournalBase($code, $type, $start_date, $end_date, $sort, $order, $pagination);

        return view('admin.layout.page', compact('default', 'journals', 'code', 'type', 'start_date', 'end_date', 'sort', 'order', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah Jurnal';
        $default['page'] = 'journal';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $journal = $this->storeJournalBase($request);

        session(['alert' => 'add', 'data' => 'Jurnal']);

        return redirect('/admin/journal/all/all/' . date('Y-m-d') . '/' . date('Y-m-d') . '/id/asc/15');
    }

    public function edit($journal_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah Jurnal';
        $default['page'] = 'journal';
        $default['section'] = 'edit';

        $journal = Journal::find($journal_id);

        return view('admin.layout.page', compact('default', 'journal'));
    }

    public function update($journal_id, Request $request)
    {
        $journal = $this->updateJournalBase($journal_id, $request);

        session(['alert' => 'edit', 'data' => 'Jurnal barang']);

        return redirect('/admin/journal/all/all/' . $journal->journal_date . '/' . $journal->journal_date . '/id/asc/15');
    }
}
