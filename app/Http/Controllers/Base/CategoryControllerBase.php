<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\Category;

trait CategoryControllerBase 
{
    public function indexCategoryBase($pagination)
    {
        if($pagination == 'all')
           $categories = Category::orderBy('name', 'asc')->get();
        else
           $categories = Category::orderBy('name', 'asc')->paginate($pagination);

        return $categories;
    }

    public function storeCategoryBase(Request $request)
    {
        $data = $request->input();

        $category = Category::create($data);

        return $category;
    }

    public function updateCategoryBase($category_id, Request $request)
    {
        $data = $request->input();

        $category = Category::find($category_id);
        $category->update($data);

        return $category;
    }

    public function deleteCategoryBase($category_id)
    {
        $category = Category::find($category_id);
        $category->delete();
    }
}
