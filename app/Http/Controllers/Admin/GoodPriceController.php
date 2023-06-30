<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\GoodPriceControllerBase;

use App\Models\GoodPrice;

class GoodPriceController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function checked($price_id)
    {
        $data['is_checked'] = 1;

        $good_price = GoodPrice::find($price_id);
        $good_price->update($data);

        return redirect('/admin');
    }
}
