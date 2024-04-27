<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\StockOpnameControllerBase;

use App\Models\StockOpname;

class StockOpnameController extends Controller
{
    use StockOpnameControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Stock Opname';
        $default['page'] = 'stock-opname';
        $default['section'] = 'all';

        $stock_opnames = $this->indexStockOpnameBase($pagination);

        return view('admin.layout.page', compact('default', 'stock_opnames', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah Stock Opname';
        $default['page'] = 'stock-opname';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $stock_opname = $this->storeStockOpnameBase('admin', \Auth::user()->id, $request);

        session(['alert' => 'add', 'data' => 'stock opname barang']);

        return redirect('/admin/stock_opname/' . $stock_opname->id . '/detail');
    }

    public function detail($stock_opname_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail Stock Opname';
        $default['page'] = 'stock-opname';
        $default['section'] = 'detail';

        $stock_opname = StockOpname::find($stock_opname_id);

        return view('admin.layout.page', compact('default', 'stock_opname'));
    }
}
