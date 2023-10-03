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
            $distributor->hutang = $distributor->totalHutangDagang()->sum('debit');
            $distributor->piutang = $distributor->totalPiutangDagang()->sum('debit');
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
