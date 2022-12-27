<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\Color;

trait ColorControllerBase 
{
    public function indexColorBase($pagination)
    {
        if($pagination == 'all')
           $colors = Color::orderBy('name', 'asc')->get();
        else
           $colors = Color::orderBy('name', 'asc')->paginate($pagination);

        return $colors;
    }

    public function storeColorBase(Request $request)
    {
    	$data = $request->input();

    	$color = Color::create($data);

        return $color;
    }

    public function updateColorBase($color_id, Request $request)
    {
    	$data = $request->input();

    	$color = Color::find($color_id);
    	$color->update($data);

        return $color;
    }

    public function deleteColorBase($color_id)
    {
    	$color = Color::find($color_id);
    	$color->delete();
    }
}
