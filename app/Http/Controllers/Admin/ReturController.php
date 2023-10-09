<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\ReturControllerBase;

class ReturController extends Controller
{
    use ReturControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($distributor_id, $status, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Barang Retur';
        $default['page'] = 'retur';
        $default['section'] = 'all';

        $returs = $this->indexReturBase($distributor_id, $status, $pagination);

        return view('admin.layout.page', compact('default', 'returs', 'distributor_id', 'status', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah Barang Retur';
        $default['page'] = 'retur';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $this->storeReturBase('admin', \Auth::user()->id, $request);

        session(['alert' => 'add', 'data' => 'Barang Retur']);

        return redirect('/admin/retur/all/null/20');
    }

    public function returItem($item_id, Request $request)
    {
        $this->returItemReturBase($item_id, $request);

        session(['alert' => 'edit', 'data' => 'Barang Retur']);

        return redirect('/admin/retur/all/null/20');
    }
}
