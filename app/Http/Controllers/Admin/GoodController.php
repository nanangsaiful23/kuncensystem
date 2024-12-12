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
        // $this->middleware('api.guard')->only(['getTransfer']);
        // $this->middleware('jwt.auth')->except(['getTransfer']);
    }

    public function index($category_id, $distributor_id, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Barang';
        $default['page'] = 'good';
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

    public function searchBygoodUnit($good_id)
    {
        $good = $this->searchBygoodUnitGoodBase($good_id);

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

    public function searchByKeywordgoodUnit($query)
    {
        $good_units = $this->searchByKeywordgoodUnitGoodBase($query);

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
        $default['page'] = 'good';
        $default['section'] = 'detail';

        $good = Good::find($good_id);

        return view('admin.layout.page', compact('default', 'good'));
    }

    public function loading($good_id, $start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Riwayat Loading Barang';
        $default['page'] = 'good';
        $default['section'] = 'loading';

        $good = Good::find($good_id);
        $loadings = $good->goodLoadingWithTrashed($pagination);

        return view('admin.layout.page', compact('default', 'loadings', 'good', 'start_date', 'end_date', 'pagination'));
    }

    public function transaction($good_id, $start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Riwayat Transaksi Barang';
        $default['page'] = 'good';
        $default['section'] = 'transaction';

        $good = Good::find($good_id);
        $transactions = $this->transactionGoodBase($good_id, $start_date, $end_date, $pagination);

        return view('admin.layout.page', compact('default', 'transactions', 'good', 'start_date', 'end_date', 'pagination'));
    }

    public function price($good_id, $start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Riwayat Harga Jual Barang';
        $default['page'] = 'good';
        $default['section'] = 'price';

        $good = Good::find($good_id);
        $prices = $this->priceGoodBase($good_id, $start_date, $end_date, $pagination);

        return view('admin.layout.page', compact('default', 'prices', 'good', 'start_date', 'end_date', 'pagination'));
    }

    public function edit($good_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah Barang';
        $default['page'] = 'good';
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

    public function exp()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Barang expired';
        $default['page'] = 'good';
        $default['section'] = 'exp';

        $loadings = $this->expGoodBase();

        return view('admin.layout.page', compact('default', 'loadings'));
    }

    public function stockExport(Request $request)
    {
        // dd($request);die;
        if($request->type == 'delete') return $this->deleteExport($request);
        $goods = [];
        foreach($request->exports as $export)
        {
            $good = Good::find($export);

            array_push($goods, [$good->getDistributor()->name, $good->name, $good->getPcsSellingPrice()->buy_price, $good->getStock()]);
        }

        return Excel::download(new ZeroStockExport($goods), 'Data Kulak ' . date('Y-m-d') . '.xlsx');
    }

    public function deleteExport(Request $request)
    {
        // dd($request);die;
        foreach($request->deletes as $delete)
        {
            $good = Good::find($delete);
            $good->delete();
        }

        session(['alert' => 'delete', 'data' => 'Barang']);

        return redirect('/admin/good/zeroStock/all/all/1/10');
    }

    public function createPrice($good_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah Harga Barang';
        $default['page'] = 'good';
        $default['section'] = 'create-good-price';

        $good = Good::find($good_id);

        return view('admin.layout.page', compact('default', 'good'));
    }

    public function storePrice($good_id, Request $request)
    {
        $good = $this->storePriceGoodBase('admin', \Auth::user()->id, $good_id, $request);

        session(['alert' => 'add', 'data' => 'Harga barang']);

        return redirect('/admin/good/' . $good->id . '/detail');
    }


    public function editPrice($good_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah Harga Barang';
        $default['page'] = 'good';
        $default['section'] = 'edit-good-price';

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
        $default['page'] = 'good';
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

    public function zeroStock($category_id, $location, $distributor_id, $stock)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Stock Habis';
        $default['page'] = 'good';
        $default['section'] = 'zero-stock';

        $goods = $this->zeroStockGoodBase($category_id, $location, $distributor_id, $stock);

        return view('admin.layout.page', compact('default', 'goods', 'category_id', 'location', 'distributor_id', 'stock'));
    }

    public function stockOpname()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Stock Opname Barang';
        $default['page'] = 'good';
        $default['section'] = 'stock-opname';

        return view('admin.layout.page', compact('default'));
    }

    public function storeStockOpname(Request $request)
    {
        $this->storeStockOpnameGoodBase('admin', \Auth::user()->id, $request);

        session(['alert' => 'add', 'data' => 'Stock Opname barang']);

        return redirect('/admin/good/stockOpname');
    }

    public function transfer()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Transfer Barang';
        $default['page'] = 'good';
        $default['section'] = 'transfer';

        return view('admin.layout.page', compact('default'));
    }

    public function storeTransfer(Request $request)
    {
        $this->storeTransferGoodBase($request);

        session(['alert' => 'add', 'data' => 'Transfer barang']);

        return redirect('/admin/good/transfer');
    }

    public function getTransfer($data)
    {
        $this->getTransferGoodBase($request);

        return api_response('success', 'ok', 200);
    }
}
