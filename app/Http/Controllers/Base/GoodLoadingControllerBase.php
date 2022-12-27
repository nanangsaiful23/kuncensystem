<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\GoodLoading;

trait GoodLoadingControllerBase 
{
    public function indexGoodLoadingBase($start_date, $end_date, $distributor_id, $pagination)
    {
        if($distributor_id == "all")
        {
            if($pagination == 'all')
            $good_loadings = GoodLoading::whereDate('good_loadings.created_at', '>=', $start_date)
                                        ->whereDate('good_loadings.created_at', '<=', $end_date) 
                                        ->orderBy('good_loadings.loading_date','asc')
                                        ->get();
            else
            $good_loadings = GoodLoading::whereDate('good_loadings.created_at', '>=', $start_date)
                                        ->whereDate('good_loadings.created_at', '<=', $end_date)
                                        ->orderBy('good_loadings.loading_date','asc')
                                        ->paginate($pagination);
        }
        else
        {
            if($pagination == 'all')
            $good_loadings = GoodLoading::whereDate('good_loadings.created_at', '>=', $start_date)
                                        ->whereDate('good_loadings.created_at', '<=', $end_date)
                                        ->where('good_loadings.distributor_id', $distributor_id)
                                        ->orderBy('good_loadings.loading_date','asc')
                                        ->get();
            else
            $good_loadings = GoodLoading::whereDate('good_loadings.created_at', '>=', $start_date)
                                        ->whereDate('good_loadings.created_at', '<=', $end_date)
                                        ->where('good_loadings.distributor_id', $distributor_id)
                                        ->orderBy('good_loadings.loading_date','asc')
                                        ->paginate($pagination);
        }

        return $good_loadings;
    }

    public function store()
    {
        
    }
}
