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

    public function index($code, $start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Jurnal';
        $default['page'] = 'journal';
        $default['section'] = 'all';

        $journals = $this->indexJournalBase($code, $start_date, $end_date, $pagination);

        return view('admin.layout.page', compact('default', 'journals', 'code', 'start_date', 'end_date', 'pagination'));
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

        return redirect('/admin/journal/all/' . date('Y-m-d') . '/' . date('Y-m-d') . '/15');
    }
}
