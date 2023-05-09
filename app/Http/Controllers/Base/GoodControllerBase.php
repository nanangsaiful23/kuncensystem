<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

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
        if($category_id == 'all' && $distributor_id == 'all')
        {
            $goods = Good::orderBy('goods.id', 'desc')->paginate($pagination);
        }
        elseif($category_id == 'all')
        {
            $goods = Good::join('good_loadings', 'good_loadings.good_id', 'goods.id')
                         ->where('good_loadings.distributor_id', $distributor_id)
                         ->orderBy('goods.id', 'desc')
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
            $goods = Good::join('good_loadings', 'good_loadings.good_id', 'goods.id')
                         ->where('good_loadings.distributor_id', $distributor_id)
                         ->where('goods.category_id', $category_id)
                         ->orderBy('goods.id', 'desc')
                         ->paginate($pagination);
        }

        return $goods;
    }

    public function searchByBarcodeGoodBase($barcode)
    {
        $good = Good::where('code', $barcode)->first();
        $good->getPcsSellingPrice = $good->getPcsSellingPrice();

        return $good;
    }

    public function searchByIdGoodBase($good_id)
    {
        $good = Good::find($good_id);

        return $good;
    }

    public function searchByKeywordGoodBase($query)
    {
        $goods = Good::where('code', 'like', '%'. $query . '%')
                     ->orWhere('name', 'like', '%'. $query . '%')
                     ->where('deleted_at', '=', null)
                     ->orderBy('name')
                     ->get();

        return $goods;
    }

    public function checkDiscountGoodBase($good_id, $quantity, $pcsPrice)
    {
        $good_unit = GoodUnit::join('units', 'good_units.unit_id', 'units.id')
                             ->where('units.quantity', '<=', $quantity)
                             ->where('good_units.good_id', $good_id)
                             ->orderBy('units.quantity', 'desc')
                             ->first();

        if($good_unit->quantity != 1)
        {
            $disc_quantity = intdiv($quantity, $good_unit->quantity);
            $real_quantity = fmod($quantity, $good_unit->quantity);
            // dd($disc_quantity . ' ' . $real_quantity);die;

            // return ($disc_quantity * (($pcsPrice * $good_unit->quantity) - $good_unit->selling_price)) + ($real_quantity * $pcsPrice);
            return $disc_quantity * (($pcsPrice * $good_unit->quantity) - $good_unit->selling_price);
        }
        else
        {
            return '0';
        }
    }

    public function getPriceUnitGoodBase($good_id, $unit_id)
    {
        $good_unit = GoodUnit::where('good_id', $good_id)->where('unit_id', $unit_id)->first();

        return $good_unit;
    }

    public function storeGoodBase(Request $request)
    {
        $data = $request->input();

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

        /*create good unit with base unit */
        $unit_base = Unit::where('quantity', 1)
                         ->where('base', $good_unit->unit->base)
                         ->first();

        $good_unit_base = GoodUnit::where('good_id', $good->id)
                                  ->where('unit_id', $unit_base->id)
                                  ->first();

        if($good_unit_base == null)
        {
            $data_unit_base['good_id']       = $good->id;
            $data_unit_base['unit_id']       = $unit_base->id;
            $data_unit_base['buy_price']     = '0';
            $data_unit_base['selling_price'] = '0';

            $good_unit_base = GoodUnit::create($data_unit_base);

            $data_price_base['role']         = $data['role'];
            $data_price_base['role_id']      = \Auth::user()->id;
            $data_price_base['good_unit_id'] = $good_unit_base->id;
            $data_price_base['old_price']    = $good_unit_base->selling_price;
            $data_price_base['recent_price'] = '0';
            $data_price_base['reason']       = 'Default 0 saat membuat barang baru bukan base unit';

            GoodPrice::create($data_price_base);
        }
        /*end*/

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
                                         ->get();
        }
        else
        {
            $loadings = GoodLoadingDetail::join('good_units', 'good_units.id', 'good_loading_details.good_unit_id')
                                         ->where('good_units.good_id', $good_id)
                                         ->whereDate('good_loading_details.created_at', '>=', $start_date)
                                         ->whereDate('good_loading_details.created_at', '<=', $end_date)
                                         ->paginate($pagination);
        }

        return $loadings;
    }

    public function transactionGoodBase($good_id, $start_date, $end_date, $pagination)
    {
        if($pagination == 'all')
        {
            $transactions = TransactionDetail::where('good_id', $good_id)
                                             ->whereDate('created_at', '>=', $start_date)
                                             ->whereDate('created_at', '<=', $end_date)
                                             ->get();
        }
        else
        {
            $transactions = TransactionDetail::where('good_id', $good_id)
                                             ->whereDate('created_at', '>=', $start_date)
                                             ->whereDate('created_at', '<=', $end_date)
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
    }

    public function deleteGoodBase($good_id)
    {
    }
}
