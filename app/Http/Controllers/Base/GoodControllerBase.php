<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\Good;

trait GoodControllerBase 
{
    public function indexGoodBase($pagination)
    {
    }

    public function searchByIdGoodBase($good_id)
    {
        $good = Good::find($good_id);

        return $good;
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

        return $good;
    }

    public function updateGoodBase($good_id, Request $request)
    {
    }

    public function deleteGoodBase($good_id)
    {
    }
}
