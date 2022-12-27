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

        $good_loadings = $this->indexGoodLoadingBase($start_date, $end_date, $distributor_id, $pagination);

        return view('admin.layout.page', compact('default', 'good_loadings', 'start_date', 'end_date', 'distributor_id', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah Loading';
        $default['page'] = 'good-loading';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default'));
    }
}
