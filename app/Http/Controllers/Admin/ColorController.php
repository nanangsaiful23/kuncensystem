<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\ColorControllerBase;

use App\Models\Color;

class ColorController extends Controller
{
    use ColorControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar warna';
        $default['page'] = 'color';
        $default['section'] = 'all';

        $colors = $this->indexColorBase($pagination);

        return view('admin.layout.page', compact('default', 'colors', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah warna';
        $default['page'] = 'color';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $color = $this->storeColorBase($request);

        session(['alert' => 'add', 'data' => 'Warna barang']);

        return redirect('/admin/color/' . $color->id . '/detail');
    }

    public function detail($color_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail warna';
        $default['page'] = 'color';
        $default['section'] = 'detail';

        $color = Color::find($color_id);

        return view('admin.layout.page', compact('default', 'color'));
    }

    public function edit($color_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah warna';
        $default['page'] = 'color';
        $default['section'] = 'edit';

        $color = Color::find($color_id);

        return view('admin.color.edit', compact('default', 'color'));
    }

    public function update($color_id, Request $request)
    {
        $color = $this->updateColorBase($color_id, $request);

        session(['alert' => 'edit', 'data' => 'Warna barang']);

        return redirect('/admin/color/' . $color->id . '/detail');
    }

    public function delete($color_id)
    {
        $this->deleteColorBase($color_id);

        session(['alert' => 'delete', 'data' => 'Warna barang']);

        return redirect('/admin/color/all/10');
    }
}
