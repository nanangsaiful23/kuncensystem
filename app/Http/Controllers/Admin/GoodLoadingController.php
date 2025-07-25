<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\GoodLoadingControllerBase;

use App\Models\GoodLoading;

class GoodLoadingController extends Controller
{
    use GoodLoadingControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($start_date, $end_date, $distributor_id, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Loading';
        $default['page'] = 'good-loading';
        $default['section'] = 'all';

        [$good_loadings, $cash] = $this->indexGoodLoadingBase($start_date, $end_date, $distributor_id, $pagination);

        return view('admin.layout.page', compact('default', 'good_loadings', 'cash', 'start_date', 'end_date', 'distributor_id', 'pagination'));
    }

    public function create($type)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        if($type == 'internal')
            $default['page_name'] = 'Tambah Loading Internal';
        elseif($type == 'transaction-internal')
            $default['page_name'] = 'Tambah Loading & Transaksi Internal';
        else
            $default['page_name'] = 'Tambah Loading';
        $default['page'] = 'good-loading';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default', 'type'));
    }

    public function store($type, Request $request)
    {
        $good_loading = $this->storeGoodLoadingBase('admin', \Auth::user()->id, $type, $request);

        session(['alert' => 'add', 'data' => 'loading barang']);

        return redirect('/admin/good-loading/' . $good_loading->id . '/detail');
    }

    public function detail($good_loading_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail Loading';
        $default['page'] = 'good-loading';
        $default['section'] = 'detail';

        $good_loading = GoodLoading::find($good_loading_id);

        return view('admin.layout.page', compact('default', 'good_loading'));
    }

    public function excel()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Import Excel Loading';
        $default['page'] = 'good-loading';
        $default['section'] = 'excel';

        return view('admin.layout.page', compact('default'));
    }

    public function storeExcel(Request $request)
    {
        $good_loading = $this->storeExcelGoodLoadingBase('admin', \Auth::user()->id, $request);

        session(['alert' => 'add', 'data' => 'loading barang']);

        return redirect('/admin/good-loading/' . $good_loading->id . '/detail');
    }

    public function print($good_loading_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Print Loading';
        $default['page'] = 'good-loading';
        $default['section'] = 'print';

        $good_loading = GoodLoading::find($good_loading_id);

        return view('layout.good-loading.print', compact('default', 'good_loading'));
    }

    public function edit($good_loading_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah Loading';
        $default['page'] = 'good-loading';
        $default['section'] = 'edit';

        $good_loading = GoodLoading::find($good_loading_id);

        return view('admin.layout.page', compact('default', 'good_loading'));
    }

    public function update($good_loading_id, Request $request)
    {
        $good_loading = $this->updateGoodLoadingBase('admin', \Auth::user()->id, $good_loading_id, $request);

        session(['alert' => 'edit', 'data' => 'Data loading']);

        return redirect('/admin/good-loading/' . $good_loading->id . '/detail');
    }

    public function delete($good_loading_id)
    {
        $this->deleteGoodLoadingBase($good_loading_id);

        session(['alert' => 'delete', 'data' => 'Loading barang']);

        return redirect('/admin/good-loading/' . date('Y-m-d') . '/' . date('Y-m-d') . '/all/20');
    }
}
