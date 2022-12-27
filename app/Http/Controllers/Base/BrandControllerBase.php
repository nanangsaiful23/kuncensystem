<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\Brand;

trait BrandControllerBase 
{
    public function indexBrandBase($pagination)
    {
        if($pagination == 'all')
           $brands = Brand::orderBy('name', 'asc')->get();
        else
           $brands = Brand::orderBy('name', 'asc')->paginate($pagination);

        return $brands;
    }

    public function storeBrandBase(Request $request)
    {
        $data = $request->input();

        $brand = Brand::create($data);

        return $brand;
    }

    public function updateBrandBase($brand_id, Request $request)
    {
        $data = $request->input();

        $brand = Brand::find($brand_id);
        $brand->update($data);

        return $brand;
    }

    public function deleteBrandBase($brand_id)
    {
        $brand = Brand::find($brand_id);
        $brand->delete();
    }
}
