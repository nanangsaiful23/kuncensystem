<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\Distributor;

trait DistributorControllerBase 
{
    public function indexDistributorBase($pagination)
    {
        if($pagination == 'all')
           $distributors = Distributor::orderBy('name', 'asc')->get();
        else
           $distributors = Distributor::orderBy('name', 'asc')->paginate($pagination);

        return $distributors;
    }

    public function searchDistributorBase($keyword)
    {
        $distributors = Distributor::where('name', 'like', '%' . $keyword . '%')
                                   ->get();

        foreach($distributors as $distributor)
        {
            $distributor->hutang = $distributor->totalHutangDagangLoading()->sum('total_item_price') - $distributor->totalHutangDagangInternal()->sum('debit');
            $distributor->piutang = $distributor->totalPiutangDagangInternal()->sum('debit') - $distributor->totalPiutangDagangLoading()->sum('total_item_price');
        }

        return $distributors;
    }

    public function storeDistributorBase(Request $request)
    {
        $data = $request->input();

        $distributor = Distributor::create($data);

        return $distributor;
    }

    public function updateDistributorBase($distributor_id, Request $request)
    {
        $data = $request->input();

        $distributor = Distributor::find($distributor_id);
        $distributor->update($data);

        return $distributor;
    }

    public function deleteDistributorBase($distributor_id)
    {
        $distributor = Distributor::find($distributor_id);
        $distributor->delete();
    }
}
