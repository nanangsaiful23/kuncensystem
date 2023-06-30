<?php

namespace App\Http\Controllers\Cashier;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\GoodPriceControllerBase;

use App\Models\GoodPrice;

class GoodPriceController extends Controller
{
    public function __construct()
    {
        $this->middleware('cashier');
    }

    public function checked($price_id)
    {
        $data['is_checked'] = 1;

        $good_price = GoodPrice::find($price_id);
        $good_price->update($data);

        return redirect('/cashier');
    }
}
