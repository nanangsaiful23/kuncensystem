<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\UnitControllerBase;

use App\Models\Unit;

class UnitController extends Controller
{
    use UnitControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Satuan';
        $default['page'] = 'unit';
        $default['section'] = 'all';

        $units = $this->indexUnitBase($pagination);

        return view('admin.layout.page', compact('default', 'units', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah Satuan';
        $default['page'] = 'unit';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $unit = $this->storeUnitBase($request);

        session(['alert' => 'add', 'data' => 'Satuan barang']);

        return redirect('/admin/unit/' . $unit->id . '/detail');
    }

    public function detail($unit_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail Satuan';
        $default['page'] = 'unit';
        $default['section'] = 'detail';

        $unit = Unit::find($unit_id);

        return view('admin.layout.page', compact('default', 'unit'));
    }

    public function edit($unit_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah Satuan';
        $default['page'] = 'unit';
        $default['section'] = 'edit';

        $unit = Unit::find($unit_id);

        return view('admin.layout.page', compact('default', 'unit'));
    }

    public function update($unit_id, Request $request)
    {
        $unit = $this->updateUnitBase($unit_id, $request);

        session(['alert' => 'edit', 'data' => 'Satuan barang']);

        return redirect('/admin/unit/' . $unit->id . '/detail');
    }

    public function delete($unit_id)
    {
        $this->deleteUnitBase($unit_id);

        session(['alert' => 'delete', 'data' => 'Satuan barang']);

        return redirect('/admin/unit/all/10');
    }
}
