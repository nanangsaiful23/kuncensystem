<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Good;
use App\Models\GoodLoadingDetail;
use App\Models\GoodPrice;
use App\Models\GoodUnit;
use App\Models\TransactionDetail;
use App\Models\Unit;

trait GoodControllerBase 
{
    public function indexGoodBase($category_id, $distributor_id, $pagination)
    {
        if($pagination == 'all')
        {
            if($category_id == 'all' && $distributor_id == 'all')
            {
                $goods = Good::orderBy('goods.id', 'desc')->get();
            }
            elseif($category_id == 'all')
            {
                $goods = Good::join('good_units', 'good_units.good_id', 'goods.id')
                             ->join('good_loading_details', 'good_loading_details.good_unit_id', 'good_units.id')
                             ->join('good_loadings', 'good_loadings.id', 'good_loading_details.good_loading_id')
                             ->select('goods.id', 'goods.name', 'goods.code', 'goods.category_id')
                             ->where('good_loadings.distributor_id', $distributor_id)
                             ->orderBy('goods.id', 'desc')
                             ->groupBy('goods.id', 'goods.name', 'goods.code', 'goods.category_id')
                             ->get();
            }
            elseif($distributor_id == 'all')
            {
                $goods = Good::where('category_id', $category_id)
                             ->orderBy('goods.id', 'desc')
                             ->get();      
            }
            else
            {
                $goods = Good::join('good_units', 'good_units.good_id', 'goods.id')
                             ->join('good_loading_details', 'good_loading_details.good_unit_id', 'good_units.id')
                             ->join('good_loadings', 'good_loadings.id', 'good_loading_details.good_loading_id')
                             ->select('goods.id', 'goods.name', 'goods.code', 'goods.category_id')
                             ->where('good_loadings.distributor_id', $distributor_id)
                             ->where('goods.category_id', $category_id)
                             ->orderBy('goods.id', 'desc')
                             ->groupBy('goods.id', 'goods.name', 'goods.code', 'goods.category_id')
                             ->get();
            }
        }
        else
        {
            if($category_id == 'all' && $distributor_id == 'all')
            {
                $goods = Good::orderBy('goods.id', 'desc')->paginate($pagination);
            }
            elseif($category_id == 'all')
            {
                $goods = Good::join('good_units', 'good_units.good_id', 'goods.id')
                             ->join('good_loading_details', 'good_loading_details.good_unit_id', 'good_units.id')
                             ->join('good_loadings', 'good_loadings.id', 'good_loading_details.good_loading_id')
                             ->select('goods.id', 'goods.name', 'goods.code', 'goods.category_id')
                             ->where('good_loadings.distributor_id', $distributor_id)
                             ->orderBy('goods.id', 'desc')
                             ->groupBy('goods.id', 'goods.name', 'goods.code', 'goods.category_id')
                             ->paginate($pagination);
            }
            elseif($distributor_id == 'all')
            {
                $goods = Good::where('category_id', $category_id)
                             ->orderBy('goods.id', 'desc')
                             ->paginate($pagination);         
            }
            else
            {
                $goods = Good::join('good_units', 'good_units.good_id', 'goods.id')
                             ->join('good_loading_details', 'good_loading_details.good_unit_id', 'good_units.id')
                             ->join('good_loadings', 'good_loadings.id', 'good_loading_details.good_loading_id')
                             ->select('goods.id', 'goods.name', 'goods.code', 'goods.category_id')
                             ->where('good_loadings.distributor_id', $distributor_id)
                             ->where('goods.category_id', $category_id)
                             ->orderBy('goods.id', 'desc')
                             ->groupBy('goods.id', 'goods.name', 'goods.code', 'goods.category_id')
                             ->paginate($pagination);
            }
        }

        return $goods;
    }

    public function searchByBarcodeGoodBase($barcode)
    {
        $good = Good::where('code', $barcode)->first();
        $good->getPcsSellingPrice = $good->getPcsSellingPrice();
        $good->stock = $good->getStock();

        return $good;
    }

    public function searchByIdGoodBase($good_id)
    {
        $good = Good::find($good_id);
        $good->getPcsSellingPrice = $good->getPcsSellingPrice();
        $good->stock = $good->getStock();

        $units = [];
        foreach($good->good_units as $unit)
        {
            $temp = [];
            $temp['good_id'] = $good->id;
            $temp['good_unit_id'] = $unit->id;
            $temp['unit_id'] = $unit->unit_id;
            $temp['code'] = $good->code;
            $temp['name'] = $good->name;
            $temp['unit'] = $unit->unit->name;
            $temp['buy_price'] = $unit->buy_price;
            $temp['selling_price'] = $unit->selling_price;
            $temp['stock'] = $good->stock;
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
        $good->stock = $good->getStock();

        return $good;
    }

    public function searchByKeywordGoodBase($query)
    {
        $goods = Good::where('code', 'like', '%'. $query . '%')
                     ->orWhere('name', 'like', '%'. $query . '%')
                     ->where('deleted_at', '=', null)
                     ->orderBy('name')
                     ->with('category')
                     // ->with('brand')
                     ->get();

        foreach($goods as $good)
        {
            $good->brand_name = $good->brand == null ? "" : $good->brand->name;
            $good->last_loading = $good->getLastBuy() == null ? "" : $good->getLastBuy()->good_loading->distributor->name . ' (' . $good->getLastBuy()->good_loading->note . ')';
            $good->stock = $good->getStock();
            $good->transaction = $good->good_transactions()->sum('real_quantity') / $good->getPcsSellingPrice()->unit->quantity;
            $good->loading = $good->good_loadings()->sum('real_quantity') / $good->getPcsSellingPrice()->unit->quantity;
            $good->unit = $good->getPcsSellingPrice() == null ? "" : $good->getPcsSellingPrice()->unit->code;

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
        $goods = Good::where('code', 'like', '%'. $query . '%')
                     ->orWhere('name', 'like', '%'. $query . '%')
                     ->where('deleted_at', '=', null)
                     ->orderBy('name')
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
                $temp['name'] = $good->name;
                $temp['unit'] = $unit->unit->name;
                $temp['buy_price'] = $unit->buy_price;
                $temp['selling_price'] = $unit->selling_price;
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
        $data = $request->input();

        $good = Good::where('name', $data['name'])->first();

        if($good == null)
        {
            $good = Good::create($data);

            if($request->code == null)
            {
                $data_good['code'] = $good->id;
                $good->update($data_good);
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

            $good->unit_id = $good_unit->unit_id;
            $good->unit    = $good_unit->unit->name . '(' . $good_unit->unit->code . ')';
            $good->price   = $data['price'];
            $good->selling_price   = $data['selling_price'];

            // /*create good unit with base unit */
            // $unit_base = Unit::where('quantity', 1)
            //                  ->where('base', $good_unit->unit->base)
            //                  ->first();

            // $good_unit_base = GoodUnit::where('good_id', $good->id)
            //                           ->where('unit_id', $unit_base->id)
            //                           ->first();

            // if($good_unit_base == null)
            // {
            //     $data_unit_base['good_id']       = $good->id;
            //     $data_unit_base['unit_id']       = $unit_base->id;
            //     $data_unit_base['buy_price']     = '0';
            //     $data_unit_base['selling_price'] = '0';

            //     $good_unit_base = GoodUnit::create($data_unit_base);

            //     $data_price_base['role']         = $data['role'];
            //     $data_price_base['role_id']      = \Auth::user()->id;
            //     $data_price_base['good_unit_id'] = $good_unit_base->id;
            //     $data_price_base['old_price']    = $good_unit_base->selling_price;
            //     $data_price_base['recent_price'] = '0';
            //     $data_price_base['reason']       = 'Default 0 saat membuat barang baru bukan base unit';

            //     GoodPrice::create($data_price_base);
            // }
            // /*end*/
        }

        return $good;
    }

    public function loadingGoodBase($good_id, $start_date, $end_date, $pagination)
    {
        if($pagination == 'all')
        {
            $loadings = GoodLoadingDetail::join('good_units', 'good_units.id', 'good_loading_details.good_unit_id')
                                         ->where('good_units.good_id', $good_id)
                                         ->whereDate('good_loading_details.loading_date', '>=', $start_date)
                                         ->whereDate('good_loading_details.loading_date', '<=', $end_date)
                                         ->where('good_units.deleted_at', null)
                                         ->get();
        }
        else
        {
            $loadings = GoodLoadingDetail::join('good_units', 'good_units.id', 'good_loading_details.good_unit_id')
                                         ->where('good_units.good_id', $good_id)
                                         ->whereDate('good_loading_details.created_at', '>=', $start_date)
                                         ->whereDate('good_loading_details.created_at', '<=', $end_date)
                                         ->where('good_units.deleted_at', null)
                                         ->paginate($pagination);
        }

        return $loadings;
    }

    public function transactionGoodBase($good_id, $start_date, $end_date, $pagination)
    {
        if($pagination == 'all')
        {
            $transactions = TransactionDetail::join('good_units', 'good_units.id', 'transaction_details.good_unit_id')
                                             ->join('goods', 'goods.id', 'good_units.good_id')
                                             ->select('transaction_details.*')
                                             ->where('goods.id', $good_id)
                                             ->whereDate('transaction_details.created_at', '>=', $start_date)
                                             ->whereDate('transaction_details.created_at', '<=', $end_date)
                                            ->where('good_units.deleted_at', null)
                                             ->orderBy('transaction_details.created_at', 'desc')
                                             ->get();
        }
        else
        {
            $transactions = TransactionDetail::join('good_units', 'good_units.id', 'transaction_details.good_unit_id')
                                             ->join('goods', 'goods.id', 'good_units.good_id')
                                             ->select('transaction_details.*')
                                             ->where('goods.id', $good_id)
                                             ->whereDate('transaction_details.created_at', '>=', $start_date)
                                             ->whereDate('transaction_details.created_at', '<=', $end_date)
                                            ->where('good_units.deleted_at', null)
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
                               ->where('good_units.good_id', $good_id)
                               ->whereDate('good_prices.created_at', '>=', $start_date)
                               ->whereDate('good_prices.created_at', '<=', $end_date)
                               ->get();
        }
        else
        {
            $prices = GoodPrice::join('good_units', 'good_units.id', 'good_prices.good_unit_id')
                               ->where('good_units.good_id', $good_id)
                               ->whereDate('good_prices.created_at', '>=', $start_date)
                               ->whereDate('good_prices.created_at', '<=', $end_date)
                               ->paginate($pagination);
        }

        return $prices;
    }

    public function updateGoodBase($good_id, Request $request)
    {
        $data = $request->input();

        $good = Good::find($good_id);
        $good->update($data);

        return $good;
    }

    public function deleteGoodBase($good_id)
    {
        $good = Good::find($good_id);
        $good->delete();

        return true;
    }

    public function zeroStockGoodBase($category_id, $location, $distributor_id, $stock)
    {
        if($category_id == 'all')
        {
            if($location == 'all')
            {
                if($distributor_id == 'all')
                {
                    $goods = DB::select(DB::raw("SELECT loading.quantity as loading, COALESCE(SUM(transaction.quantity), 0) as transaction, goods.id
                                      FROM goods 
                                      LEFT JOIN (SELECT COALESCE(SUM(good_loading_details.real_quantity), 0) as quantity, good_units.good_id
                                                FROM good_loading_details
                                                LEFT JOIN good_units ON good_units.id = good_loading_details.good_unit_id
                                                WHERE good_units.deleted_at IS NULL
                                                GROUP BY good_units.good_id) as loading ON loading.good_id = goods.id
                                      LEFT JOIN (SELECT COALESCE(SUM(transaction_details.real_quantity), 0) as quantity, good_units.good_id
                                                FROM transaction_details
                                                LEFT JOIN good_units ON good_units.id = transaction_details.good_unit_id
                                                WHERE good_units.deleted_at IS NULL
                                                GROUP BY good_units.good_id) as transaction ON transaction.good_id = goods.id
                                      WHERE goods.deleted_at IS NULL
                                      GROUP BY goods.id, loading.quantity, transaction.quantity
                                      HAVING (loading - transaction) <= " . $stock));
                }
                else
                {
                    $goods = DB::select(DB::raw("SELECT loading.quantity as loading, COALESCE(SUM(transaction.quantity), 0) as transaction, goods.id 
                                      FROM (SELECT goods.id 
                                            FROM goods 
                                            JOIN good_units ON good_units.good_id = goods.id
                                            JOIN good_loading_details ON good_units.id = good_loading_details.good_unit_id
                                            JOIN good_loadings ON good_loadings.id = good_loading_details.good_loading_id
                                            JOIN distributors ON distributors.id = good_loadings.distributor_id
                                            WHERE distributors.id = " . $distributor_id . " 
                                            AND goods.deleted_at IS NULL
                                            AND good_units.deleted_at IS NULL
                                            GROUP BY goods.id) as goods
                                      LEFT JOIN (SELECT COALESCE(SUM(good_loading_details.real_quantity), 0) as quantity, good_units.good_id
                                                FROM good_loading_details
                                                LEFT JOIN good_units ON good_units.id = good_loading_details.good_unit_id
                                                WHERE good_units.deleted_at IS NULL
                                                GROUP BY good_units.good_id) as loading ON loading.good_id = goods.id
                                      LEFT JOIN (SELECT COALESCE(SUM(transaction_details.real_quantity), 0) as quantity, good_units.good_id
                                                FROM transaction_details
                                                LEFT JOIN good_units ON good_units.id = transaction_details.good_unit_id
                                                WHERE good_units.deleted_at IS NULL
                                                GROUP BY good_units.good_id) as transaction ON transaction.good_id = goods.id
                                      GROUP BY goods.id, loading.quantity, transaction.quantity
                                      HAVING (loading - transaction) <= " . $stock));
                }
            }
            else
            {
                if($distributor_id == 'all')
                {
                    $goods = DB::select(DB::raw("SELECT loading.quantity as loading, COALESCE(SUM(transaction.quantity), 0) as transaction, goods.id
                                      FROM (SELECT goods.id 
                                            FROM goods 
                                            JOIN good_units ON good_units.good_id = goods.id
                                            JOIN good_loading_details ON good_units.id = good_loading_details.good_unit_id
                                            JOIN good_loadings ON good_loadings.id = good_loading_details.good_loading_id
                                            JOIN distributors ON distributors.id = good_loadings.distributor_id
                                            WHERE distributors.location = '" . $location . "' 
                                            AND good_units.deleted_at IS NULL
                                            AND goods.deleted_at IS NULL
                                            GROUP BY goods.id) as goods
                                      LEFT JOIN (SELECT COALESCE(SUM(good_loading_details.real_quantity), 0) as quantity, good_units.good_id
                                                FROM good_loading_details
                                                LEFT JOIN good_units ON good_units.id = good_loading_details.good_unit_id
                                                WHERE good_units.deleted_at IS NULL
                                                GROUP BY good_units.good_id) as loading ON loading.good_id = goods.id
                                      LEFT JOIN (SELECT COALESCE(SUM(transaction_details.real_quantity), 0) as quantity, good_units.good_id
                                                FROM transaction_details
                                                LEFT JOIN good_units ON good_units.id = transaction_details.good_unit_id
                                                WHERE good_units.deleted_at IS NULL
                                                GROUP BY good_units.good_id) as transaction ON transaction.good_id = goods.id
                                      GROUP BY goods.id, loading.quantity, transaction.quantity
                                      HAVING (loading - transaction) <= " . $stock));
                }
                else
                {
                    $goods = DB::select(DB::raw("SELECT loading.quantity as loading, COALESCE(SUM(transaction.quantity), 0) as transaction, goods.id 
                                      FROM (SELECT goods.id
                                            FROM goods 
                                            JOIN good_units ON good_units.good_id = goods.id
                                            JOIN good_loading_details ON good_units.id = good_loading_details.good_unit_id
                                            JOIN good_loadings ON good_loadings.id = good_loading_details.good_loading_id
                                            JOIN distributors ON distributors.id = good_loadings.distributor_id
                                            WHERE distributors.location = '" . $location . "' AND 
                                            distributors.id = " . $distributor_id . " 
                                            AND good_units.deleted_at IS NULL
                                            AND goods.deleted_at IS NULL
                                            GROUP BY goods.id) as goods
                                      LEFT JOIN (SELECT COALESCE(SUM(good_loading_details.real_quantity), 0) as quantity, good_units.good_id
                                                FROM good_loading_details
                                                LEFT JOIN good_units ON good_units.id = good_loading_details.good_unit_id
                                                WHERE good_units.deleted_at IS NULL
                                                GROUP BY good_units.good_id) as loading ON loading.good_id = goods.id
                                      LEFT JOIN (SELECT COALESCE(SUM(transaction_details.real_quantity), 0) as quantity, good_units.good_id
                                                FROM transaction_details
                                                LEFT JOIN good_units ON good_units.id = transaction_details.good_unit_id
                                                WHERE good_units.deleted_at IS NULL
                                                GROUP BY good_units.good_id) as transaction ON transaction.good_id = goods.id
                                      GROUP BY goods.id, loading.quantity, transaction.quantity
                                      HAVING (loading - transaction) <= " . $stock));
                }
            }
        }
        else
        {
            if($location == 'all')
            {
                if($distributor_id == 'all')
                {
                    $goods = DB::select(DB::raw("SELECT loading.quantity as loading, COALESCE(SUM(transaction.quantity), 0) as transaction, goods.id
                                      FROM goods 
                                      LEFT JOIN (SELECT COALESCE(SUM(good_loading_details.real_quantity), 0) as quantity, good_units.good_id
                                                FROM good_loading_details
                                                LEFT JOIN good_units ON good_units.id = good_loading_details.good_unit_id
                                                WHERE good_units.deleted_at IS NULL
                                                GROUP BY good_units.good_id) as loading ON loading.good_id = goods.id
                                      LEFT JOIN (SELECT COALESCE(SUM(transaction_details.real_quantity), 0) as quantity, good_units.good_id
                                                FROM transaction_details
                                                LEFT JOIN good_units ON good_units.id = transaction_details.good_unit_id
                                                WHERE good_units.deleted_at IS NULL
                                                GROUP BY good_units.good_id) as transaction ON transaction.good_id = goods.id
                                      WHERE goods.category_id = " . $category_id . " 
                                      AND goods.deleted_at IS NULL
                                      GROUP BY goods.id, loading.quantity, transaction.quantity
                                      HAVING (loading - transaction) <= " . $stock));
                }
                else
                {
                    $goods = DB::select(DB::raw("SELECT loading.quantity as loading, COALESCE(SUM(transaction.quantity), 0) as transaction, goods.id 
                                      FROM (SELECT goods.id 
                                            FROM goods 
                                            JOIN good_units ON good_units.good_id = goods.id
                                            JOIN good_loading_details ON good_units.id = good_loading_details.good_unit_id
                                            JOIN good_loadings ON good_loadings.id = good_loading_details.good_loading_id
                                            JOIN distributors ON distributors.id = good_loadings.distributor_id
                                            WHERE distributors.id = " . $distributor_id . " 
                                            AND goods.category_id = " . $category_id . " 
                                            AND goods.deleted_at IS NULL 
                                            AND good_units.deleted_at IS NULL
                                            GROUP BY goods.id) as goods
                                      LEFT JOIN (SELECT COALESCE(SUM(good_loading_details.real_quantity), 0) as quantity, good_units.good_id
                                                FROM good_loading_details
                                                LEFT JOIN good_units ON good_units.id = good_loading_details.good_unit_id
                                                WHERE good_units.deleted_at IS NULL
                                                GROUP BY good_units.good_id) as loading ON loading.good_id = goods.id
                                      LEFT JOIN (SELECT COALESCE(SUM(transaction_details.real_quantity), 0) as quantity, good_units.good_id
                                                FROM transaction_details
                                                LEFT JOIN good_units ON good_units.id = transaction_details.good_unit_id
                                                WHERE good_units.deleted_at IS NULL
                                                GROUP BY good_units.good_id) as transaction ON transaction.good_id = goods.id
                                      GROUP BY goods.id, loading.quantity, transaction.quantity
                                      HAVING (loading - transaction) <= " . $stock));
                }
            }
            else
            {
                if($distributor_id == 'all')
                {
                    $goods = DB::select(DB::raw("SELECT loading.quantity as loading, COALESCE(SUM(transaction.quantity), 0) as transaction, goods.id
                                      FROM (SELECT goods.id 
                                            FROM goods 
                                            JOIN good_units ON good_units.good_id = goods.id
                                            JOIN good_loading_details ON good_units.id = good_loading_details.good_unit_id
                                            JOIN good_loadings ON good_loadings.id = good_loading_details.good_loading_id
                                            JOIN distributors ON distributors.id = good_loadings.distributor_id
                                            WHERE distributors.location = '" . $location . "'
                                            AND goods.category_id = " . $category_id . " 
                                            AND goods.deleted_at IS NULL 
                                            AND good_units.deleted_at IS NULL
                                            GROUP BY goods.id) as goods
                                      LEFT JOIN (SELECT COALESCE(SUM(good_loading_details.real_quantity), 0) as quantity, good_units.good_id
                                                FROM good_loading_details
                                                LEFT JOIN good_units ON good_units.id = good_loading_details.good_unit_id
                                                WHERE good_units.deleted_at IS NULL
                                                GROUP BY good_units.good_id) as loading ON loading.good_id = goods.id
                                      LEFT JOIN (SELECT COALESCE(SUM(transaction_details.real_quantity), 0) as quantity, good_units.good_id
                                                FROM transaction_details
                                                LEFT JOIN good_units ON good_units.id = transaction_details.good_unit_id
                                                WHERE good_units.deleted_at IS NULL
                                                GROUP BY good_units.good_id) as transaction ON transaction.good_id = goods.id
                                      GROUP BY goods.id, loading.quantity, transaction.quantity
                                      HAVING (loading - transaction) <= " . $stock));
                }
                else
                {
                    $goods = DB::select(DB::raw("SELECT loading.quantity as loading, COALESCE(SUM(transaction.quantity), 0) as transaction, goods.id 
                                      FROM (SELECT goods.id
                                            FROM goods 
                                            JOIN good_units ON good_units.good_id = goods.id
                                            JOIN good_loading_details ON good_units.id = good_loading_details.good_unit_id
                                            JOIN good_loadings ON good_loadings.id = good_loading_details.good_loading_id
                                            JOIN distributors ON distributors.id = good_loadings.distributor_id
                                            WHERE distributors.location = '" . $location . "' 
                                            AND distributors.id = " . $distributor_id . "
                                            AND goods.category_id = " . $category_id . "
                                            AND goods.deleted_at IS NULL
                                            AND good_units.deleted_at IS NULL
                                            GROUP BY goods.id) as goods
                                      LEFT JOIN (SELECT COALESCE(SUM(good_loading_details.real_quantity), 0) as quantity, good_units.good_id
                                                FROM good_loading_details
                                                LEFT JOIN good_units ON good_units.id = good_loading_details.good_unit_id
                                                WHERE good_units.deleted_at IS NULL
                                                GROUP BY good_units.good_id) as loading ON loading.good_id = goods.id
                                      LEFT JOIN (SELECT COALESCE(SUM(transaction_details.real_quantity), 0) as quantity, good_units.good_id
                                                FROM transaction_details
                                                LEFT JOIN good_units ON good_units.id = transaction_details.good_unit_id
                                                WHERE good_units.deleted_at IS NULL
                                                GROUP BY good_units.good_id) as transaction ON transaction.good_id = goods.id
                                      GROUP BY goods.id, loading.quantity, transaction.quantity
                                      HAVING (loading - transaction) <= " . $stock));
                }
            }
        }

        foreach($goods as $good)
        {
            $good->obj = Good::find($good->id);
        }
        
        return $goods;
    }

    public function updatePriceGoodBase($role, $role_id, $good_id, Request $request)
    {
        $good = Good::find($good_id);

        for($i = 0; $i < sizeof($request->good_unit_ids); $i++)
        {
            $good_unit = GoodUnit::find($request->good_unit_ids[$i]);

            $data_price['role'] = $role;
            $data_price['role_id'] = $role_id;
            $data_price['good_unit_id'] = $good_unit->id;
            $data_price['old_price'] = $good_unit->selling_price;
            $data_price['recent_price'] = $request->selling_prices[$i];
            $data_price['reason'] = $request->reason;

            GoodPrice::create($data_price);

            $data_good_unit['selling_price'] = $request->selling_prices[$i];

            $good_unit->update($data_good_unit);
        }

        return $good;
    }

    public function deletePriceGoodBase($good_unit_id)
    {
        $good_unit = GoodUnit::find($good_unit_id);
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

                for($j = 0; $j < $request->quantities[$i]; $j++)
                {
                //     foreach($good->good_units as $unit)
                //     {
                    $data['name'] = $good_unit->good->name;
                    $data['unit'] = $good_unit->unit->name;
                    $data['price'] = $good_unit->selling_price;

                    array_push($goods, $data);
                //     } 
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
}
