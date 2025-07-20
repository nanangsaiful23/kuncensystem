<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\DistributorControllerBase;

use App\Models\Distributor;
use App\Models\Good;

class DistributorController extends Controller
{
    use DistributorControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Distributor';
        $default['page'] = 'distributor';
        $default['section'] = 'all';

        $distributors = $this->indexDistributorBase($pagination);


        $total = Good::join('good_units', 'good_units.id', 'goods.base_unit_id')
                       ->selectRaw('SUM(goods.last_stock * good_units.buy_price) as total')
                       ->get();

        $total = $total[0]->total;

        return view('admin.layout.page', compact('default', 'distributors', 'total', 'pagination'));
    }

    public function search($keyword)
    {
        $distributors = $this->searchDistributorBase($keyword);

        return response()->json([
            "distributors"  => $distributors
        ], 200);
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah Distributor';
        $default['page'] = 'distributor';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $distributor = $this->storeDistributorBase($request);

        session(['alert' => 'add', 'data' => 'distributor barang']);

        return redirect('/admin/distributor/' . $distributor->id . '/detail');
    }

    public function detail($distributor_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail Distributor';
        $default['page'] = 'distributor';
        $default['section'] = 'detail';

        $distributor = Distributor::find($distributor_id);

        return view('admin.layout.page', compact('default', 'distributor'));
    }

    public function edit($distributor_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah Distributor';
        $default['page'] = 'distributor';
        $default['section'] = 'edit';

        $distributor = Distributor::find($distributor_id);

        return view('admin.layout.page', compact('default', 'distributor'));
    }

    public function update($distributor_id, Request $request)
    {
        $distributor = $this->updateDistributorBase($distributor_id, $request);

        session(['alert' => 'edit', 'data' => 'distributor barang']);

        return redirect('/admin/distributor/' . $distributor->id . '/detail');
    }

    public function delete($distributor_id)
    {
        $this->deleteDistributorBase($distributor_id);

        session(['alert' => 'delete', 'data' => 'distributor barang']);

        return redirect('/admin/distributor/all/10');
    }

    public function creditPayment($distributor_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $distributor = Distributor::find($distributor_id);

        $default['page_name'] = 'Pembayaran Hutang ' . $distributor->name;
        $default['page'] = 'journal';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default', 'distributor'));
    }
}
