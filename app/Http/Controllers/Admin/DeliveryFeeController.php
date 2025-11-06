<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\DeliveryFeeControllerBase;

use App\Models\DeliveryFee;

class DeliveryFeeController extends Controller
{
    use DeliveryFeeControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Delivery Fee';
        $default['page'] = 'delivery-fee';
        $default['section'] = 'all';

        $fees = $this->indexDeliveryFeeBase($pagination);

        return view('admin.layout.page', compact('default', 'fees', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah Delivery Fee';
        $default['page'] = 'delivery-fee';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $fee = $this->storeDeliveryFeeBase($request);

        session(['alert' => 'add', 'data' => 'Delivery Fee']);

        return redirect('/admin/delivery-fee/' . $fee->id . '/detail');
    }

    public function search($keyword)
    {
        $fees = $this->searchDeliveryFeeBase($keyword);

        return response()->json([
            "fees"  => $fees
        ], 200);
    }

    public function detail($fee_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail Delivery Fee';
        $default['page'] = 'delivery-fee';
        $default['section'] = 'detail';

        $fee = DeliveryFee::find($fee_id);

        return view('admin.layout.page', compact('default', 'fee'));
    }

    public function edit($fee_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah Delivery Fee';
        $default['page'] = 'delivery-fee';
        $default['section'] = 'edit';

        $fee = DeliveryFee::find($fee_id);

        return view('admin.layout.page', compact('default', 'fee'));
    }

    public function update($fee_id, Request $request)
    {
        $fee = $this->updateDeliveryFeeBase($fee_id, $request);

        session(['alert' => 'edit', 'data' => 'Delivery Fee']);

        return redirect('/admin/delivery-fee/' . $fee->id . '/detail');
    }

    public function delete($fee_id)
    {
        $this->deleteDeliveryFeeBase($fee_id);

        session(['alert' => 'delete', 'data' => 'Delivery Fee']);

        return redirect('/admin/delivery-fee/all/10');
    }
}
