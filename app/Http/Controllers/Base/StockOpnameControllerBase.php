<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\StockOpname;
use App\Services\StockOpnameService;

trait StockOpnameControllerBase
{
    public function indexStockOpnameBase($start_date, $end_date, $pagination)
    {
        if($pagination == 'all')
        $stock_opnames = StockOpname::whereDate('stock_opnames.created_at', '>=', $start_date)
                                    ->whereDate('stock_opnames.created_at', '<=', $end_date)
                                    ->orderBy('stock_opnames.created_at','asc')
                                    ->get();
        else
        $stock_opnames = StockOpname::whereDate('stock_opnames.created_at', '>=', $start_date)
                                    ->whereDate('stock_opnames.created_at', '<=', $end_date)
                                    ->orderBy('stock_opnames.created_at','asc')
                                    ->paginate($pagination);

        return $stock_opnames;
    }

    /**
     * Stok dikoreksi per barang, jurnalnya satu per stock opname
     * (lihat App\Services\StockOpnameService).
     */
    public function storeStockOpnameBase($role, $role_id, Request $request)
    {
        return StockOpnameService::simpan($role, $role_id, $request->note, $request->checker,
                                          StockOpnameService::barisDariRequest($request));
    }
}
