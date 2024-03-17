<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\DeliveryFee;

trait DeliveryFeeControllerBase 
{
    public function indexDeliveryFeeBase($pagination)
    {
        if($pagination == 'all')
           $fees = DeliveryFee::orderBy('location', 'asc')->get();
        else
           $fees = DeliveryFee::orderBy('location', 'asc')->paginate($pagination);

        return $fees;
    }

    public function storeDeliveryFeeBase(Request $request)
    {
        $data = $request->input();

        $fee = DeliveryFee::create($data);

        return $fee;
    }

    public function updateDeliveryFeeBase($delivery_id, Request $request)
    {
        $data = $request->input();

        $fee = DeliveryFee::find($delivery_id);
        $fee->update($data);

        return $fee;
    }

    public function deleteDeliveryFeeBase($delivery_id)
    {
        $fee = DeliveryFee::find($delivery_id);
        $fee->delete();
    }
}
