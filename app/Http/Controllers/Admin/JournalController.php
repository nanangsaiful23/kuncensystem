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
}
