<?php

namespace App\Http\Controllers\Admin;

use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ZeroStockExport;

use App\Http\Controllers\Base\GoodControllerBase;

use App\Models\Good;

class GoodController extends Controller
{
    use GoodControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($category_id, $distributor_id, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Barang';
        $default['page'] = 'Good';
        $default['section'] = 'all';

        $goods = $this->indexGoodBase($category_id, $distributor_id, $pagination);

        return view('admin.layout.page', compact('default', 'goods', 'category_id', 'distributor_id', 'pagination'));
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
        $units = $this->searchByIdGoodBase($good_id);

        return response()->json([
            "units"  => $units
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
        $stock = Good::find($good_id)->getStock();

        return response()->json([
            "discount"  => $discount,
            "stock"     => $stock
        ], 200);
    }

    public function getPriceUnit($good_id, $unit_id)
    {
        $good_unit = $this->getPriceUnitGoodBase($good_id, $unit_id);

        return response()->json([
            "good_unit"  => $good_unit
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

        return view('admin.layout.page', compact('default', 'good'));
    }

    public function loading($good_id, $start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Riwayat Loading Barang';
        $default['page'] = 'Good';
        $default['section'] = 'loading';

        $good = Good::find($good_id);
        $loadings = $this->loadingGoodBase($good_id, $start_date, $end_date, $pagination);

        return view('admin.layout.page', compact('default', 'loadings', 'good', 'start_date', 'end_date', 'pagination'));
    }

    public function transaction($good_id, $start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Riwayat Transaksi Barang';
        $default['page'] = 'Good';
        $default['section'] = 'transaction';

        $good = Good::find($good_id);
        $transactions = $this->transactionGoodBase($good_id, $start_date, $end_date, $pagination);

        return view('admin.layout.page', compact('default', 'transactions', 'good', 'start_date', 'end_date', 'pagination'));
    }

    public function price($good_id, $start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Riwayat Harga Jual Barang';
        $default['page'] = 'Good';
        $default['section'] = 'price';

        $good = Good::find($good_id);
        $prices = $this->priceGoodBase($good_id, $start_date, $end_date, $pagination);

        return view('admin.layout.page', compact('default', 'prices', 'good', 'start_date', 'end_date', 'pagination'));
    }

    public function edit($good_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah Barang';
        $default['page'] = 'Good';
        $default['section'] = 'edit';

        $good = Good::find($good_id);

        return view('admin.layout.page', compact('default', 'good'));
    }

    public function update($good_id, Request $request)
    {
        $good = $this->updateGoodBase($good_id, $request);

        session(['alert' => 'edit', 'data' => 'Data barang']);

        return redirect('/admin/good/' . $good->id . '/detail');
    }

    public function delete($good_id)
    {
        $this->deleteGoodBase($good_id);

        session(['alert' => 'delete', 'data' => 'Barang']);

        return redirect('/admin/good/all/all/20');
    }

    public function zeroStock($category_id, $location, $distributor_id, $stock)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Stock Habis';
        $default['page'] = 'good';
        $default['section'] = 'zero-stock';

        $goods = $this->zeroStockGoodBase($category_id, $location, $distributor_id, $stock);

        return view('admin.layout.page', compact('default', 'goods', 'category_id', 'location', 'distributor_id', 'stock'));
    }

    public function stockExport(Request $request)
    {
        $goods = [];
        foreach($request->exports as $export)
        {
            $good = Good::find($export);

            array_push($goods, [$good->getLastBuy()->good_loading->distributor->name, $good->name, $good->getLastBuy()->price, $good->getStock()]);
        }

        return Excel::download(new ZeroStockExport($goods), 'Data Kulak ' . date('Y-m-d') . '.xlsx');
    }

    public function editPrice($good_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah Harga Barang';
        $default['page'] = 'Good';
        $default['section'] = 'good-price';

        $good = Good::find($good_id);

        return view('admin.layout.page', compact('default', 'good'));
    }

    public function updatePrice($good_id, Request $request)
    {
        $good = $this->updatePriceGoodBase('admin', \Auth::user()->id, $good_id, $request);

        session(['alert' => 'edit', 'data' => 'Harga barang']);

        return redirect('/admin/good/' . $good->id . '/detail');
    }

    public function deletePrice($good_id, $good_unit_id)
    {
        $this->deletePriceGoodBase($good_unit_id);

        session(['alert' => 'delete', 'data' => 'Harga barang']);

        return redirect('/admin/good/' . $good_id . '/detail');
    }

    public function choosePrintDisplay()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Print Harga Display Barang';
        $default['page'] = 'Good';
        $default['section'] = 'choose-print-display';

        return view('admin.layout.page', compact('default'));
    }

    public function printDisplay(Request $request)
    {
        $role = 'admin';

        $goods = $this->printDisplayGoodBase($request);
        
        if($request->type == 'rack')
            return view('layout.good.print-display-rack', compact('role', 'goods'));
        else
            return view('layout.good.print-display-list', compact('role', 'goods'));
    }
}
