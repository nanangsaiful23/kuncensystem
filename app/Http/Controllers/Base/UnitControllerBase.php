<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\Unit;

trait UnitControllerBase 
{
    public function indexUnitBase($pagination)
    {
        if($pagination == 'all')
           $units = Unit::orderBy('base', 'asc')->get();
        else
           $units = Unit::orderBy('base', 'asc')->paginate($pagination);

        return $units;
    }

    public function storeUnitBase(Request $request)
    {
    	$data = $request->input();

    	$unit = Unit::create($data);

        return $unit;
    }

    public function updateUnitBase($unit_id, Request $request)
    {
    	$data = $request->input();

    	$unit = Unit::find($unit_id);
    	$unit->update($data);

        return $unit;
    }

    public function deleteUnitBase($unit_id)
    {
    	$unit = Unit::find($unit_id);
    	$unit->delete();
    }
}
