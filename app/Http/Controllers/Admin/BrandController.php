<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\BrandControllerBase;

use App\Models\Brand;

class BrandController extends Controller
{
    use BrandControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Brand';
        $default['page'] = 'brand';
        $default['section'] = 'all';

        $brands = $this->indexBrandBase($pagination);

        return view('admin.layout.page', compact('default', 'brands', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah Brand';
        $default['page'] = 'brand';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $brand = $this->storeBrandBase($request);

        session(['alert' => 'add', 'data' => 'Brand barang']);

        return redirect('/admin/brand/' . $brand->id . '/detail');
    }

    public function detail($brand_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail Brand';
        $default['page'] = 'brand';
        $default['section'] = 'detail';

        $brand = Brand::find($brand_id);

        return view('admin.layout.page', compact('default', 'brand'));
    }

    public function edit($brand_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah Brand';
        $default['page'] = 'brand';
        $default['section'] = 'edit';

        $brand = Brand::find($brand_id);

        return view('admin.layout.page', compact('default', 'brand'));
    }

    public function update($brand_id, Request $request)
    {
        $brand = $this->updateBrandBase($brand_id, $request);

        session(['alert' => 'edit', 'data' => 'Brand barang']);

        return redirect('/admin/brand/' . $brand->id . '/detail');
    }

    public function delete($brand_id)
    {
        $this->deleteBrandBase($brand_id);

        session(['alert' => 'delete', 'data' => 'Brand barang']);

        return redirect('/admin/brand/all/10');
    }

    public function good($brand_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Barang Brand';
        $default['page'] = 'brand';
        $default['section'] = 'good';

        $brand = Brand::find($brand_id);

        return view('admin.layout.page', compact('default', 'brand'));
    }
}
