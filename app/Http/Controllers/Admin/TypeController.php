<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\TypeControllerBase;

use App\Models\Type;

class TypeController extends Controller
{
    use TypeControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Jenis Barang';
        $default['page'] = 'type';
        $default['section'] = 'all';

        $types = $this->indexTypeBase($pagination);

        return view('admin.layout.page', compact('default', 'types', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah Jenis Barang';
        $default['page'] = 'type';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $type = $this->storeTypeBase($request);

        session(['alert' => 'add', 'data' => 'Jenis barang']);

        return redirect('/admin/type/' . $type->id . '/detail');
    }

    public function detail($type_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail Jenis Barang';
        $default['page'] = 'type';
        $default['section'] = 'detail';

        $type = Type::find($type_id);

        return view('admin.layout.page', compact('default', 'type'));
    }

    public function edit($type_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah Jenis Barang';
        $default['page'] = 'type';
        $default['section'] = 'edit';

        $type = Type::find($type_id);

        return view('admin.layout.page', compact('default', 'type'));
    }

    public function update($type_id, Request $request)
    {
        $type = $this->updateTypeBase($type_id, $request);

        session(['alert' => 'edit', 'data' => 'Jenis barang']);

        return redirect('/admin/type/' . $type->id . '/detail');
    }

    public function delete($type_id)
    {
        $this->deleteTypeBase($type_id);

        session(['alert' => 'delete', 'data' => 'Jenis barang']);

        return redirect('/admin/type/all/10');
    }
}
