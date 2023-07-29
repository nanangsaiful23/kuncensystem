<?php

namespace App\Http\Controllers\Cashier;

use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\GoodControllerBase;

use App\Models\Good;

class GoodController extends Controller
{
    use GoodControllerBase;

    public function __construct()
    {
        $this->middleware('cashier');
    }

    public function index($category_id, $distributor_id, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Barang';
        $default['page'] = 'Good';
        $default['section'] = 'all';

        $goods = $this->indexGoodBase($category_id, $distributor_id, $pagination);

        return view('cashier.layout.page', compact('default', 'goods', 'category_id', 'distributor_id', 'pagination'));
    }

    public function searchByBarcode($barcode)
    {
        $good = $this->searchByBarcodeGoodBase($barcode);

        return response()->json([
            "good"  => $good
        ], 200);
    }

    public function searchById($good_id)
    {
        $good = $this->searchByIdGoodBase($good_id);

        return response()->json([
            "good"  => $good
        ], 200);
    }

    public function searchByGoodUnit($good_id)
    {
        $good = $this->searchByGoodUnitGoodBase($good_id);

        return response()->json([
            "good"  => $good
        ], 200);
    }

    public function searchByKeyword($query)
    {
        $goods = $this->searchByKeywordGoodBase($query);

        return response()->json([
            "goods"  => $goods
        ], 200);
    }

    public function searchByKeywordGoodUnit($query)
    {
        $good_units = $this->searchByKeywordGoodUnitGoodBase($query);

        return response()->json([
            "good_units"  => $good_units
        ], 200);
    }

    public function checkDiscount($good_id, $quantity, $price)
    {
        $discount = $this->checkDiscountGoodBase($good_id, $quantity, $price);

        return response()->json([
            "discount"  => $discount
        ], 200);
    }

    public function store(Request $request)
    {
        $good = $this->storeGoodBase($request);

        return response()->json([
            "good"  => $good
        ], 200);
    }

    public function detail($good_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail Barang';
        $default['page'] = 'Good';
        $default['section'] = 'detail';

        $good = Good::find($good_id);

        return view('cashier.layout.page', compact('default', 'good'));
    }

    public function transaction($good_id, $start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Riwayat Transaksi Barang';
        $default['page'] = 'Good';
        $default['section'] = 'transaction';

        $good = Good::find($good_id);
        $transactions = $this->transactionGoodBase($good_id, $start_date, $end_date, $pagination);

        return view('cashier.layout.page', compact('default', 'transactions', 'good', 'start_date', 'end_date', 'pagination'));
    }

    public function price($good_id, $start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Riwayat Harga Jual Barang';
        $default['page'] = 'Good';
        $default['section'] = 'price';

        $good = Good::find($good_id);
        $prices = $this->priceGoodBase($good_id, $start_date, $end_date, $pagination);

        return view('cashier.layout.page', compact('default', 'prices', 'good', 'start_date', 'end_date', 'pagination'));
    }

    public function edit($good_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah Barang';
        $default['page'] = 'Good';
        $default['section'] = 'edit';

        $good = Good::find($good_id);

        return view('cashier.layout.page', compact('default', 'good'));
    }

    public function update($good_id, Request $request)
    {
        $good = $this->updateGoodBase($good_id, $request);

        session(['alert' => 'edit', 'data' => 'Good barang']);

        return redirect('/cashier/good/' . $good->id . '/detail');
    }
}
