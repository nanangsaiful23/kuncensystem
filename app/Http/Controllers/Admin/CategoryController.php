<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\CategoryControllerBase;

use App\Models\Category;

class CategoryController extends Controller
{
    use CategoryControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Kategori';
        $default['page'] = 'category';
        $default['section'] = 'all';

        $categories = $this->indexCategoryBase($pagination);

        return view('admin.layout.page', compact('default', 'categories', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah Kategori';
        $default['page'] = 'category';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $category = $this->storeCategoryBase($request);

        session(['alert' => 'add', 'data' => 'Kategori barang']);

        return redirect('/admin/category/' . $category->id . '/detail');
    }

    public function detail($category_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail Kategori';
        $default['page'] = 'category';
        $default['section'] = 'detail';

        $category = Category::find($category_id);

        return view('admin.layout.page', compact('default', 'category'));
    }

    public function edit($category_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah Kategori';
        $default['page'] = 'category';
        $default['section'] = 'edit';

        $category = Category::find($category_id);

        return view('admin.layout.page', compact('default', 'category'));
    }

    public function update($category_id, Request $request)
    {
        $category = $this->updateCategoryBase($category_id, $request);

        session(['alert' => 'edit', 'data' => 'Kategori barang']);

        return redirect('/admin/category/' . $category->id . '/detail');
    }

    public function delete($category_id)
    {
        $this->deleteCategoryBase($category_id);

        session(['alert' => 'delete', 'data' => 'Kategori barang']);

        return redirect('/admin/category/all/10');
    }
}
