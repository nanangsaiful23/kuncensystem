<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\journalControllerBase;

use App\Models\Journal;

class JournalController extends Controller
{
    use journalControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Jurnal';
        $default['page'] = 'journal';
        $default['section'] = 'all';

        $journals = $this->indexJournalBase($start_date, $end_date, $pagination);

        return view('admin.layout.page', compact('default', 'journals', 'start_date', 'end_date', 'pagination'));
    }
}
