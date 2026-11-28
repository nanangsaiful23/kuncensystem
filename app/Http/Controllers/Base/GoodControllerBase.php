<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Imports\ChangeNameImport;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\Account;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Distributor;
use App\Models\Good;
use App\Models\GoodLoading;
use App\Models\GoodLoadingDetail;
use App\Models\GoodPrice;
use App\Models\GoodUnit;
use App\Models\Journal;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use App\Models\Unit;

trait GoodControllerBase 
{
    public function indexGoodBase($category_id, $type_id, $distributor_id, $sort, $order, $pagination)
    {
        if($category_id == 'all')
            $whereCategory = '%%';
        else
            $whereCategory = $category_id;

        if($type_id == 'all')
            $whereType = '%%';
        else
            $whereType = $type_id;

        if($distributor_id == 'all')
            $whereDistributor = '%%';
        else
            $whereDistributor = $distributor_id;

        if($pagination == 'all')
        {
            if($category_id == 'all' && $distributor_id == 'all' && $type_id == 'all')
            {
                $goods = Good::orderBy($sort, $order)->get();
            }
            else
            {
                $goods = Good::leftjoin('good_units', 'good_units.good_id', 'goods.id')
                             ->leftjoin('good_loading_details', 'good_loading_details.good_unit_id', 'good_units.id')
                             ->leftjoin('good_loadings', 'good_loadings.id', 'good_loading_details.good_loading_id')
                             ->leftjoin('types', 'types.id', 'goods.type_id')
                             ->select('goods.id', 'goods.name', 'goods.code', 'goods.category_id', 'goods.last_distributor_id', 'goods.total_loading', 'goods.total_transaction', 'goods.last_stock', 'goods.last_loading', 'goods.last_transaction', 'types.id as type_id')
                             ->whereRaw("coalesce(goods.category_id, '') like ? AND coalesce(goods.type_id, '') like ? AND coalesce(goods.last_distributor_id, '') like ? ", array($whereCategory, $whereType, $whereDistributor))
                             ->orderBy($sort, $order)
                             ->groupBy('goods.id', 'goods.name', 'goods.code', 'goods.category_id', 'goods.last_distributor_id', 'goods.total_loading', 'goods.total_transaction', 'goods.last_stock', 'goods.last_loading', 'goods.last_transaction', 'types.id')
                             ->get();
            }
        }
        else
        {
            if($category_id == 'all' && $distributor_id == 'all' && $type_id == 'all')
            {
                $goods = Good::orderBy($sort, $order)->paginate($pagination);
            }
            else
            {
                $goods = Good::leftjoin('good_units', 'good_units.good_id', 'goods.id')
                             ->leftjoin('good_loading_details', 'good_loading_details.good_unit_id', 'good_units.id')
                             ->leftjoin('good_loadings', 'good_loadings.id', 'good_loading_details.good_loading_id')
                             ->leftjoin('types', 'types.id', 'goods.type_id')
                             ->select('goods.id', 'goods.name', 'goods.code', 'goods.category_id', 'goods.last_distributor_id', 'goods.total_loading', 'goods.total_transaction', 'goods.last_stock', 'goods.last_loading', 'goods.last_transaction', 'types.id as type_id')
                             ->whereRaw("coalesce(goods.category_id, '') like ? AND coalesce(goods.type_id, '') like ? AND coalesce(goods.last_distributor_id, '') like ? ", array($whereCategory, $whereType, $whereDistributor))
                             ->orderBy($sort, $order)
                             ->groupBy('goods.id', 'goods.name', 'goods.code', 'goods.category_id', 'goods.last_distributor_id', 'goods.total_loading', 'goods.total_transaction', 'goods.last_stock', 'goods.last_loading', 'goods.last_transaction', 'types.id')
                             ->paginate($pagination);
            }
        }

        return $goods;
    }

    public function searchByBarcodeGoodBase($barcode)
    {
        $good = Good::where('code', $barcode)->first();
        if($good != null)
        {
            $good_unit = $good->base_unit();
            $good_unit->name = $good_unit->unit->name;
            $good->getPcsSellingPrice = $good_unit;
            $good->stock = $good->last_stock;
            $good->sale_units = $this->saleUnitsGoodBase($good->id);

            return $good;
        }
        else
            return null;
    }

    /**
     * Semua satuan jual satu barang beserta isinya (units.quantity).
     * Halaman kasir memakainya untuk otomatis memakai harga satuan besar:
     * 6 PCS (3.500) dengan PAK5 (15.000) dihitung 1 PAK5 + 1 PCS.
     */
    private function saleUnitsGoodBase($good_id)
    {
        return GoodUnit::with('unit')
                       ->where('good_id', $good_id)
                       ->get()
                       ->filter(fn ($good_unit) => $good_unit->unit != null)
                       ->map(fn ($good_unit) => [
                           'good_unit_id'  => $good_unit->id,
                           'unit'          => $good_unit->unit->name,
                           'unit_qty'      => $good_unit->unit->quantity,
                           'selling_price' => $good_unit->selling_price,
                           'buy_price'     => $good_unit->buy_price,
                       ])
                       ->values();
    }

    public function searchByIdGoodBase($good_id)
    {
        $good = Good::find($good_id);
        // $good->getPcsSellingPrice = $good->getPcsSellingPrice();
        // $good->stock = $good->getStock();

        $units = [];
        foreach($good->good_units as $unit)
        {
            $temp = [];
            $temp['good_id'] = $good->id;
            $temp['good_unit_id'] = $unit->id;
            $temp['unit_id'] = $unit->unit_id;
            $temp['unit_qty'] = $unit->unit->quantity;
            $temp['good_base_qty'] = $good->base_unit()->unit->quantity;
            $temp['good_base_buy_price'] = $good->base_unit()->buy_price;
            $temp['stock'] = $good->last_stock;
            $temp['code'] = $good->code;
            $temp['name'] = $good->getFullName();
            $temp['good_type'] = $good->getType();
            $temp['unit'] = $unit->unit->name;
            $temp['buy_price'] = $unit->buy_price;
            $temp['selling_price'] = $unit->selling_price;
            array_push($units, $temp);
        }

        return $units;
    }

    public function searchByGoodUnitGoodBase($good_unit_id)
    {
        $good_unit = GoodUnit::find($good_unit_id);
        $good_unit->name = $good_unit->unit->name;

        $good = Good::find($good_unit->good_id);
        $good->getPcsSellingPrice = $good_unit;
        $good->stock = $good->last_stock;

        if($good->stock == 0)
            $good->status = '[KOSONG]';
        elseif($good->stock < 0)
            $good->status = ' [MINUS]';
        $good->name = $good->getFullName();

        return $good;
    }

    public function searchByKeywordGoodBase($query)
    {
        // $query = '%' . $query . '%';
        $goods = Good::leftjoin('types', 'goods.type_id', 'types.id')
                     ->select('goods.*')
                     // ->whereRaw("goods.code like ? OR goods.name like ? OR types.name like ? ", ['%'. $query . '%', '%'. $query . '%', '%'. $query . '%'])
                     ->where('goods.code', 'like', '%'. $query . '%')
                     ->orWhere('goods.name', 'like', '%'. $query . '%')
                     ->orWhere('types.name', 'like', '%'. $query . '%')
                     ->where('goods.deleted_at', '=', null)
                     ->orderBy('goods.name')
                     ->with('category')
                     // ->with('brand')
                     ->get();

        $outflows = \App\Services\StockOutflow::forDisplay($goods);

        foreach($goods as $good)
        {
            $good->outflow_text = \App\Services\StockOutflow::summary($outflows[$good->id] ?? [], $good->base_unit()->unit->code);
            $good->last_loading_date = displayDate($good->last_loading);
            $good->last_transaction_date = displayDate($good->last_transaction);
            $good->brand_name = $good->brand == null ? "" : $good->brand->name;
            $good->last_loading = $good->getLastBuy() == null ? $good->getDistributor()->name : $good->getDistributor()->name . ' (' . $good->getLastBuy()->good_loading->note . ')';
            $good->stock = $good->last_stock;
            $good->transaction = $good->total_transaction;
            $good->loading = $good->total_loading;
            $good->unit = $good->base_unit()->unit->code;
            $good->good_type = $good->getType();
            $good->short_name = $good->name;
            $good->name = $good->getFullName();

            foreach($good->good_units as $unit)
            {
                $unit->price = showRupiah(roundMoney($unit->selling_price));
                $unit->profit = showRupiah(roundMoney($unit->selling_price) - checkNull($unit->buy_price));
                $unit->percentage = calculateProfit(checkNull($unit->buy_price), roundMoney($unit->selling_price));
                $unit->unit_name = $unit->unit->name;
                $unit->unit_id = $unit->unit->id;
                $unit->buy_price = showRupiah(roundMoney(checkNull($unit->buy_price)));
            }
        }

        return $goods;
    }

    public function searchByKeywordGoodUnitGoodBase($query)
    {
        // Dibatasi dan relasinya dimuat sekaligus: sebelumnya kata umum (mis. "air")
        // mengambil semua barang yang cocok lalu menjalankan query tambahan per
        // barang dan per satuan (ratusan-ribuan query) dan mengirim JSON besar
        // lewat internet toko yang lambat.
        $like  = '%' . $query . '%';
        $goods = Good::leftjoin('types', 'goods.type_id', 'types.id')
                     ->select('goods.*')
                     ->where(function ($q) use ($like) {
                         $q->where('goods.code', 'like', $like)
                           ->orWhere('goods.name', 'like', $like)
                           ->orWhere('types.name', 'like', $like);
                     })
                     ->with(['type', 'good_units.unit'])
                     ->orderBy('goods.name')
                     ->limit(100)
                     ->get();

        $units = [];
        foreach($goods as $good)
        {
            foreach($good->good_units as $unit)
            {
                $temp = [];
                $temp['good_id'] = $good->id;
                $temp['good_unit_id'] = $unit->id;
                $temp['unit_id'] = $unit->unit_id;
                $temp['code'] = $good->code;
                $temp['unit'] = $unit->unit->name;
                $temp['unit_qty'] = $unit->unit->quantity;   // isi satuan: untuk harga satuan besar otomatis di kasir
                $temp['buy_price'] = $unit->buy_price;
                $temp['selling_price'] = $unit->selling_price;
                $temp['stock'] = $good->last_stock;
                if($temp['stock'] == 0)
                    $temp['status'] = '[KOSONG]';
                elseif($temp['stock'] < 0)
                    $temp['status'] = '[MINUS]';
                $temp['name'] = $good->getFullName();
                $temp['good_type'] = $good->getType();
                array_push($units, $temp);
            }
        }

        return $units;
    }

    public function checkDiscountGoodBase($good_id, $quantity, $pcsPrice)
    {
        // $good_unit = GoodUnit::join('units', 'good_units.unit_id', 'units.id')
        //                      ->where('units.quantity', '<=', $quantity)
        //                      ->where('good_units.good_id', $good_id)
        //                      ->orderBy('units.quantity', 'desc')
        //                      ->first();

        // if($good_unit == null)
        // {
        //     return 0;
        // }
        // else
        // {
        //     if($good_unit->quantity != 1)
        //     {
        //         if($quantity < 1) $disc_quantity = 0;
        //         else $disc_quantity = intdiv($quantity, $good_unit->quantity);

        //         $real_quantity = fmod($quantity, $good_unit->quantity);
        //         // dd($disc_quantity . ' ' . $real_quantity);die;

        //         // return ($disc_quantity * (($pcsPrice * $good_unit->quantity) - $good_unit->selling_price)) + ($real_quantity * $pcsPrice);
        //         return $disc_quantity * (($pcsPrice * $good_unit->quantity) - $good_unit->selling_price);
        //     }
        //     else
        //     {
        //         return '0';
        //     }
        // }
        return '0';
    }

    public function getPriceUnitGoodBase($good_id, $unit_id)
    {
        $good_unit = GoodUnit::where('good_id', $good_id)->where('unit_id', $unit_id)->first();

        return $good_unit;
    }

    public function storeGoodBase(Request $request)
    {
        // "12.500", "12,500" and "12500" all mean the same price (parseNumber)
        $request->merge([
            'price'         => parseNumber($request->input('price')),
            'selling_price' => parseNumber($request->input('selling_price')),
        ]);
        $this->validate($request, [
            'price' => array('required', 'numeric', 'min:0'),
            'selling_price' => array('required', 'numeric', 'min:0'),
        ]);
        abortOnBadPrices([[
            'label' => trim((string) $request->input('name')) ?: 'Barang baru',
            'buy'   => $request->input('price'),
            'sell'  => $request->input('selling_price'),
        ]]);

        $data = $request->input();
        if($data['category_id'] == null)
            $data['category_id'] = 1;

        $good = Good::where('name', $data['name'])->first();

        if($good == null)
        {
            $data_good['total_loading'] = 0;
            $data_good['total_transaction'] = 0;
            $data_good['last_stock'] = 0;

            $good = Good::create($data);

            if($request->code == null)
            {
                $data_good['code'] = $good->id;
            } 

            $good_unit = GoodUnit::where('good_id', $good->id)
                                 ->where('unit_id', $data['unit_id'])
                                 ->first();

            if($good_unit)
            {
                if($good_unit->selling_price != $data['selling_price'])
                {
                    $data_price['role']         = $data['role'];
                    $data_price['role_id']      = \Auth::user()->id;
                    $data_price['good_unit_id'] = $good_unit->id;
                    $data_price['old_price']    = $good_unit->selling_price;
                    $data_price['recent_price'] = $data['selling_price'];
                    $data_price['reason']       = 'Diubah saat loading';

                    GoodPrice::create($data_price);
                }

                $data_unit['buy_price']     = $data['price'];
                $data_unit['selling_price'] = $data['selling_price'];

                $good_unit->update($data_unit);
            }
            else
            {
                $data_unit['good_id']       = $good->id;
                $data_unit['unit_id']       = $data['unit_id'];
                $data_unit['buy_price']     = $data['price'];
                $data_unit['selling_price'] = $data['selling_price'];

                $good_unit = GoodUnit::create($data_unit);

                $data_price['role']         = $data['role'];
                $data_price['role_id']      = \Auth::user()->id;
                $data_price['good_unit_id'] = $good_unit->id;
                $data_price['old_price']    = $good_unit->selling_price;
                $data_price['recent_price'] = $data['selling_price'];
                $data_price['reason']       = 'Harga pertama';

                GoodPrice::create($data_price);
            }

            $data_good['base_unit_id'] = $good->base_unit()->id;
            $good->update($data_good);

            $good->unit_id = $good_unit->unit_id;
            $good->unit    = $good_unit->unit->name . '(' . $good_unit->unit->code . ')';
            $good->price   = $data['price'];
            $good->selling_price   = $data['selling_price'];
        }

        return $good;
    }

    public function transactionGoodBase($good_id, $start_date, $end_date, $pagination)
    {
        if($pagination == 'all')
        {
            $transactions = TransactionDetail::join('good_units', 'good_units.id', 'transaction_details.good_unit_id')
                                             ->join('goods', 'goods.id', 'good_units.good_id')
                                             ->join('transactions', 'transactions.id', 'transaction_details.transaction_id')
                                             ->select('transaction_details.*')
                                             ->where('goods.id', $good_id)
                                             ->whereDate('transaction_details.created_at', '>=', $start_date)
                                             ->whereDate('transaction_details.created_at', '<=', $end_date)
                                            ->where('good_units.deleted_at', null)
                                            ->where('transactions.deleted_at', null)
                                             ->orderBy('transaction_details.created_at', 'desc')
                                             ->get();
        }
        else
        {
            $transactions = TransactionDetail::join('good_units', 'good_units.id', 'transaction_details.good_unit_id')
                                             ->join('goods', 'goods.id', 'good_units.good_id')
                                             ->join('transactions', 'transactions.id', 'transaction_details.transaction_id')
                                             ->select('transaction_details.*')
                                             ->where('goods.id', $good_id)
                                             ->whereDate('transaction_details.created_at', '>=', $start_date)
                                             ->whereDate('transaction_details.created_at', '<=', $end_date)
                                            ->where('good_units.deleted_at', null)
                                            ->where('transactions.deleted_at', null)
                                             ->orderBy('transaction_details.created_at', 'desc')
                                             ->paginate($pagination);
        }

        return $transactions;
    }

    public function priceGoodBase($good_id, $start_date, $end_date, $pagination)
    {
        if($pagination == 'all')
        {
            $prices = GoodPrice::join('good_units', 'good_units.id', 'good_prices.good_unit_id')
                               ->select('good_prices.created_at', 'good_prices.*')
                               ->where('good_units.good_id', $good_id)
                               ->whereDate('good_prices.created_at', '>=', $start_date)
                               ->whereDate('good_prices.created_at', '<=', $end_date)
                               ->where('good_units.deleted_at', null)
                               ->orderBy('good_prices.created_at', 'desc')
                               ->get();
        }
        else
        {
            $prices = GoodPrice::join('good_units', 'good_units.id', 'good_prices.good_unit_id')
                               ->select('good_prices.created_at', 'good_prices.*')
                               ->where('good_units.good_id', $good_id)
                               ->whereDate('good_prices.created_at', '>=', $start_date)
                               ->whereDate('good_prices.created_at', '<=', $end_date)
                               ->where('good_units.deleted_at', null)
                               ->orderBy('good_prices.created_at', 'desc')
                               ->paginate($pagination);
        }

        return $prices;
    }

    public function updateGoodBase($good_id, Request $request)
    {
        $data = $request->input();
        $data['code'] = null;
        // Stock only changes through loadings / transactions, never by editing the good.
        unset($data['total_loading'], $data['total_transaction'], $data['last_stock']);

        $good = Good::find($good_id);
        $good->update($data);

        $code_temp['code'] = $request->code;
        $good->update($code_temp);

        return $good;
    }

    public function deleteGoodBase($good_id)
    {
        $good = Good::find($good_id);
        $data_good['code'] = $good->id;
        $good->update($data_good);
        $good->delete();

        return true;
    }

    public function zeroStockGoodBase($main_category_id, $category_id, $location, $distributor_id, $stock, $pagination)
    {
        if($main_category_id == 'all')
            $whereMainCategory = '%%';
        else
            $whereMainCategory = $main_category_id;

        if($category_id == 'all')
            $whereCategory = '%%';
        else
            $whereCategory = $category_id;

        if($location == 'all')
            $whereLocation = '%%';
        else
            $whereLocation = $location;

        if($distributor_id == 'all')
            $whereDistributor = '%%';
        else
            $whereDistributor = $distributor_id;

        if($main_category_id == 'all' && $category_id == 'all' && $location == 'all' && $distributor_id == 'all')
        {
            if($pagination == 'all')
            {
                $goods = Good::where('last_stock', '<=', $stock)
                             ->orderBy('last_loading', 'desc')
                             ->get();
            }
            else
            {
                $goods = Good::where('last_stock', '<=', $stock)
                             ->orderBy('last_loading', 'desc')
                             ->paginate($pagination);
            }
        }
        else
        {
            if($pagination == 'all')
            {
                $goods = Good::join('categories', 'categories.id', 'goods.category_id')
                             ->leftjoin('distributors', 'distributors.id', 'goods.last_distributor_id')
                             ->select('goods.*')
                             ->where('goods.last_stock', '<=', $stock)
                             ->whereRaw('coalesce(categories.main_category_id, "") like ? AND goods.category_id like ? AND coalesce(distributors.location, "") like ? AND coalesce(goods.last_distributor_id, "") like ?', array($whereMainCategory, $whereCategory, $whereLocation, $whereDistributor))
                             ->orderBy('goods.last_loading', 'desc')
                             ->get();
            }
            else
            {
                $goods = Good::join('categories', 'categories.id', 'goods.category_id')
                             ->leftjoin('distributors', 'distributors.id', 'goods.last_distributor_id')
                             ->select('goods.*')
                             ->where('goods.last_stock', '<=', $stock)
                             ->whereRaw('coalesce(categories.main_category_id, "") like ? AND goods.category_id like ? AND coalesce(distributors.location, "") like ? AND coalesce(goods.last_distributor_id, "") like ?', array($whereMainCategory, $whereCategory, $whereLocation, $whereDistributor))
                             ->orderBy('goods.last_loading', 'desc')
                             ->paginate($pagination);
            }
        }
        
        return $goods;
    }

    public function storePriceGoodBase($role, $role_id, $good_id, Request $request)
    {
        $this->validate($request, [
            'unit_id' => array('required'),
            'buy_price' => array('required', 'regex:/^-?(?:\d+|\d{1,3}(?:,\d{3})+)(?:(\.|,)\d+)?$/'),
            'selling_price' => array('required', 'regex:/^-?(?:\d+|\d{1,3}(?:,\d{3})+)(?:(\.|,)\d+)?$/'),
        ]);
        abortOnBadPrices([[
            'label' => 'Satuan ' . (optional(Unit::find($request->unit_id))->name ?? '?'),
            'buy'   => $request->buy_price,
            'sell'  => $request->selling_price,
        ]]);

        $data = $request->input();
        $data['buy_price'] = unformatNumber($request->buy_price);
        $data['selling_price'] = unformatNumber($request->selling_price);
        $data['good_id'] = $good_id;

        $good_unit = GoodUnit::where('good_id', $good_id)
                             ->where('unit_id', $request->unit_id)
                             ->first();

        if($good_unit)
        {
            $good_unit->update($data);
        }
        else
        {
            $good_unit = GoodUnit::create($data);
        }

        $data_price['role'] = $role;
        $data_price['role_id'] = $role_id;
        $data_price['good_unit_id'] = $good_unit->id;
        $data_price['old_buy_price'] = 0;
        $data_price['recent_buy_price'] = $good_unit->buy_price;
        $data_price['old_price'] = 0;
        $data_price['recent_price'] = $good_unit->selling_price;
        $data_price['reason'] = $request->reason;

        GoodPrice::create($data_price);

        $good = $good_unit->good;
        $data_good['base_unit_id'] = $good->getPcsSellingPrice()->id;
        $good->update($data_good);

        return $good;
    }

    public function updatePriceGoodBase($role, $role_id, $good_id, Request $request)
    {
        // $request->buy_prices = unformatNumber($request->buy_prices);
        // $request->selling_prices = unformatNumber($request->selling_prices);
        $this->validate($request, [
            'buy_prices.*' => array('required', 'regex:/^-?(?:\d+|\d{1,3}(?:,\d{3})+)(?:(\.|,)\d+)?$/'),
            'selling_prices.*' => array('required', 'regex:/^-?(?:\d+|\d{1,3}(?:,\d{3})+)(?:(\.|,)\d+)?$/'),
        ]);

        $good = Good::find($good_id);
        $laba_goods = [];

        $checks = [];
        foreach ((array) $request->good_unit_ids as $i => $good_unit_id) {
            $checks[] = [
                'label' => 'Satuan ' . (optional(optional(GoodUnit::find($good_unit_id))->unit)->name ?? '?'),
                'buy'   => $request->buy_prices[$i] ?? null,
                'sell'  => $request->selling_prices[$i] ?? null,
            ];
        }
        abortOnBadPrices($checks);

        for($i = 0; $i < sizeof($request->good_unit_ids); $i++)
        {
            $good_unit = GoodUnit::find($request->good_unit_ids[$i]);

            $data_price['role'] = $role;
            $data_price['role_id'] = $role_id;
            $data_price['good_unit_id'] = $good_unit->id;
            $data_price['old_buy_price'] = $good_unit->buy_price;
            $data_price['recent_buy_price'] = unformatNumber($request->buy_prices[$i]);
            $data_price['old_price'] = $good_unit->selling_price;
            $data_price['recent_price'] = unformatNumber($request->selling_prices[$i]);
            $data_price['reason'] = $request->reason;

            GoodPrice::create($data_price);

            $data_good_unit['buy_price'] = unformatNumber($request->buy_prices[$i]);
            $data_good_unit['selling_price'] = unformatNumber($request->selling_prices[$i]);

            if((floatval($good_unit->buy_price) < floatval($data_good_unit['buy_price'])) && $good_unit->good->getStock() > 0 && !in_array($good_unit->good->id, $laba_goods))
            {
                $account_buy = Account::where('code', '1141')->first();

                $amount = $good_unit->good->getStock() * (($data_good_unit['buy_price'] - $good_unit->buy_price) / $good_unit->unit->quantity);

                $data_payment_buy['type']               = 'other_payment';
                $data_payment_buy['journal_date']       = date('Y-m-d');
                $data_payment_buy['name']               = 'Laba kenaikan harga barang ' . $good_unit->good->getFullName() . ' id good unit ' . $good_unit->id . ' (dari menu riwayat perubahan harga jual)';
                $data_payment_buy['debit_account_id']   = $account_buy->id;
                $data_payment_buy['debit']              = $amount;
                $data_payment_buy['credit_account_id']  = Account::where('code', '5215')->first()->id;
                $data_payment_buy['credit']             = $amount;

                Journal::create($data_payment_buy);

                array_push($laba_goods, $good_unit->good->id);
            }
            elseif((floatval($good_unit->buy_price) > floatval($data_good_unit['buy_price'])) && $good_unit->good->getStock() > 0 && !in_array($good_unit->good->id, $laba_goods)) #journal penyusutan kalau harga beli turun
            {
                $account_buy = Account::where('code', '5215')->first();

                $amount = $good_unit->good->getStock() * (($good_unit->buy_price - $data_good_unit['buy_price']) / $good_unit->unit->quantity);

                $data_payment_buy['type']               = 'other_payment';
                $data_payment_buy['journal_date']       = date('Y-m-d');
                $data_payment_buy['name']               = $account_buy->name . ' (penyusutan harga barang ' . $good_unit->good->getFullName() . ' id good unit ' . $good_unit->id . '(dari menu riwayat perubahan harga jual)';
                $data_payment_buy['debit_account_id']   = $account_buy->id;
                $data_payment_buy['debit']              = $amount;
                $data_payment_buy['credit_account_id']  = Account::where('code', '1141')->first()->id;
                $data_payment_buy['credit']             = $amount;

                Journal::create($data_payment_buy);

                array_push($laba_goods, $good_unit->good->id);
            }

            $good_unit->update($data_good_unit);
        }

        return $good;
    }

    public function deletePriceGoodBase($good_unit_id)
    {
        $good_unit = GoodUnit::find($good_unit_id);

        $good = $good_unit->good;
        $data_good['base_unit_id'] = $good->getPcsSellingPrice()->id;
        $good->update($data_good);
        
        $good_unit->delete();

        return true;
    }

    public function printDisplayGoodBase(Request $request)
    {
        // dd($request);die;
        $goods = [];
        for($i = 0; $i < sizeof($request->ids); $i++)
        {
            if($request->ids[$i] != null)
            {
                $good_unit = GoodUnit::find($request->ids[$i]);
                $good = $good_unit->good;

                for($j = 0; $j < $request->quantities[$i]; $j++)
                {
                    $data['name'] = $good->getFullName();
                    $data['price'] = [];
                    foreach($good->good_units as $unit)
                    {
                        $price['unit'] = $unit->unit->name;
                        $price['price'] = $unit->selling_price;

                        array_push($data['price'], $price);
                    } 
                    array_push($goods, $data);
                }
            }
        }
        
        return $goods;
    }

    public function expGoodBase()
    {
        $today = Carbon::now();
        $later = $today->addDays(15);

        $loadings = GoodLoadingDetail::whereDate('good_loading_details.expiry_date', '>=', date('Y-m-d'))
                                  ->whereDate('good_loading_details.expiry_date', '<=', $later)
                                  ->orderBy('good_loading_details.expiry_date', 'asc')
                                  ->get();

        return $loadings;
    }

    /** Sama dengan menu Stock Opname: jurnalnya satu per stock opname (App\Services\StockOpnameService). */
    public function storeStockOpnameGoodBase($role, $role_id, Request $request)
    {
        \App\Services\StockOpnameService::simpan($role, $role_id, $request->note, $request->checker,
                                                 \App\Services\StockOpnameService::barisDariRequest($request));

        return true;
    }

    public function storeTransferGoodBase(Request $request)
    {
        for($i = 0; $i < sizeof($request->names); $i++)
        {
            if($request->names[$i] != null)
            {
                $good = Good::find($request->names[$i]);
                $data[$i]['good_name'] = $good->name;
                $data[$i]['good_last_distributor_id'] = $good->last_distributor_id;

                $data[$i]['category_code'] = $good->category->code;
                $data[$i]['category_name'] = $good->category->name;
                $data[$i]['category_eng_name'] = $good->category->eng_name;
                $data[$i]['category_unit_id'] = $good->category->unit_id;

                $data[$i]['brand_name'] = $good->brand == null ? null : $good->brand->name;

                if($good->last_distributor_id != null)
                {
                    $distributor = Distributor::find($good->last_distributor_id);
                    $data[$i]['distributor_name'] = $distributor->name;
                    $data[$i]['distributor_location'] = $distributor->location; 
                }

                $unit = Unit::find($request->units[$i]);
                $data[$i]['unit_code'] = $unit->code;
                $data[$i]['unit_name'] = $unit->name;
                $data[$i]['unit_eng_name'] = $unit->eng_name;
                $data[$i]['unit_quantity'] = $unit->quantity;
                $data[$i]['unit_base'] = $unit->base;

                $good_unit = GoodUnit::where('good_id', $good->id)
                                     ->where('unit_id', $unit->id)
                                     ->first();

                $data[$i]['good_unit_buy_price'] = $good_unit->buy_price;
                $data[$i]['good_unit_selling_price'] = $good_unit->selling_price;
            }
        }
        dd($data);die;

        $result = callPostGuzzle($request->address . '/good/getTransfer', Cookie::get('token'), $data);

        if($result["status"] == "ok")
        {
            $photo = $result['data']->photo;
            $languages = $result2['data']->languages;

            return redirect('/uphoto/' . Crypt::encrypt($photo->id));
        }
        elseif($result["code"] == 422)
        {
            session(['alert' => 'error', 'data' => 'Photo']);

            return redirect('/uphoto/create');
        }
        elseif($result["code"] == 500)
        {
            return redirect('/login');
        }
        elseif($result["code"] == 401)
        {
            return redirect('/login')->withCookie(Cookie::forget('token'));
        }
    }

    public function getTransferGoodBase($data)
    {
        for($i = 0; $i < sizeof($data); $i++)
        {
            $category = Category::where('code', $data[$i]['category_code'])->first();

            if($category == null)
            {
                $data_category['code'] = $data[$i]['category_code'];
                $data_category['name'] = $data[$i]['category_name'];
                $data_category['eng_name'] = $data[$i]['category_eng_name'];
                $data_category['unit_id'] = $data[$i]['category_unit_id'];

                $category = Category::create($data_category);
            }

            if($data[$i]['brand_name'] != null)
            {
                $brand = Brand::where('name', $data[$i]['brand_name'])->first();

                if($brand == null)
                {
                    $data_brand['name'] = $data[$i]['brand_name'];

                    $brand = Brand::create($data_brand);
                }
                $data_good['brand_id'] = $brand->id;
            }
            else
            {
                $data_good['brand_id'] = null;
            }

            if($data[$i]['good_last_distributor_id'] != null)
            {
                $distributor = Distributor::where('name', $data[$i]['distributor_name'])->first();

                if($distributor == null)
                {
                    $data_distributor['name'] = $data[$i]['distributor_name'];
                    $data_distributor['location'] = $data[$i]['distributor_location'];

                    $distributor = Distributor::create($data_distributor);
                }
                $data_good['last_distributor_id'] = $distributor->id;
            }
            else
            {
                $data_good['last_distributor_id'] = null;
            }

            $unit = Unit::where('code', $data[$i]['unit_code'])->first();

            if($unit == null)
            { 
                $data_unit['code'] = $data[$i]['unit_code'];
                $data_unit['name'] = $data[$i]['unit_name'];
                $data_unit['eng_name'] = $data[$i]['unit_eng_name'];
                $data_unit['quantity'] = $data[$i]['unit_quantity'];
                $data_unit['base'] = $data[$i]['unit_base'];

                $unit = Unit::create($data_unit);
            }

            $data_good['category_id'] = $category->id;
            $data_good['name'] = $data[$i]['good_name'];

            $good = Good::create($data_good);
            $data_good['code'] = $good->id;
            $good->update($data_good);

            $data_good_unit['good_id'] = $good->id;
            $data_good_unit['unit_id'] = $unit->id;
            $data_good_unit['buy_price'] = $data[$i]['good_unit_buy_price'];
            $data_good_unit['selling_price'] = $data[$i]['good_unit_selling_price'];

            GoodUnit::create($data_good_unit);
        }

        return true;
    }

    public function getPopularGoodsGoodBase($time_limit, $limit)
    {
        $today = Carbon::now();
        $old_days = $today->subDays($time_limit);

        $goods = TransactionDetail::select(DB::raw('SUM(transaction_details.quantity) AS total, goods.name as good_name, types.name as type_name, units.name as unit_name, good_photos.location, good_units.id as guid'))
                                  ->join('good_units', 'good_units.id', 'transaction_details.good_unit_id')
                                  ->join('goods', 'goods.id', 'good_units.good_id')
                                  ->leftJoin('types', 'types.id', 'goods.type_id')
                                  ->join('units', 'units.id', 'good_units.unit_id')
                                  ->join('good_photos', 'goods.id', 'good_photos.good_id')
                                  ->whereDate('transaction_details.created_at', '>=', $old_days)
                                  ->whereDate('transaction_details.created_at', '<=', date('Y-m-d'))
                                  ->where('good_photos.is_profile_picture', 1)
                                  ->where('transaction_details.type', 'normal')
                                  ->orderBy('total', 'desc')
                                  ->groupBy('goods.name')
                                  ->groupBy('types.name')
                                  ->groupBy('units.name')
                                  ->groupBy('good_photos.location')
                                  ->groupBy('good_units.id')
                                  ->take($limit)
                                  ->get();

        return $goods;
    }

    public function resumeGoodBase($sort, $order, $pagination)
    {

        return $goods;
    }

    public function wholesalePriceGoodBase()
    {
          $perPage = 20;
          $currentPage = request('page', 1);


          $query = "FROM goods 
                     LEFT JOIN good_units ON good_units.good_id = goods.id
                     LEFT JOIN units ON units.id = good_units.unit_id
                     LEFT JOIN (SELECT goods.id, goods.name, good_units.selling_price AS base_price, units.quantity
                        FROM goods 
                        JOIN good_units ON good_units.id = goods.base_unit_id
                        JOIN units ON units.id = good_units.unit_id
                        WHERE good_units.deleted_at IS NULL AND goods.deleted_at IS NULL AND units.deleted_at IS NULL) AS base_price ON base_price.id = goods.id
                     WHERE good_units.id != goods.base_unit_id AND (base_price * units.quantity) < good_units.selling_price AND base_price.quantity < units.quantity AND good_units.deleted_at IS NULL AND goods.deleted_at IS NULL AND units.deleted_at IS NULL
                     GROUP BY goods.id, goods.code, goods.name, good_units.selling_price, units.quantity, goods.last_stock";

          $totalCount = DB::select("SELECT COUNT(*) as total " . $query);

          $result = DB::select("SELECT goods.id, goods.code, goods.name, base_price.base_price, base_price.quantity as base_qty, good_units.selling_price, units.quantity, goods.last_stock " . $query . " LIMIT " . $perPage . " OFFSET " . ($currentPage - 1) * $perPage);

          Good::applyFullNames($result);

          $paginator = new \Illuminate\Pagination\LengthAwarePaginator($result, sizeof($totalCount), $perPage, $currentPage, ['path' => url('/admin/good/wholesalePrice')]);
          
          return $paginator;
        
    }

    public function changeNameImportGoodBase(Request $request)
    {
        if($request->hasFile('file')) 
        {
            Excel::import(new ChangeNameImport(), $request->file('file'));
        }

        return true;
    }

    public function changeDistGoodBase($good_id, Request $request)
    {
        $good = Good::find($good_id);

        if($good != null)
        {
            $data['last_distributor_id'] = $request->last_distributor_id;
            $good->update($data);

            return true;
        }

        return false;
    }
}
