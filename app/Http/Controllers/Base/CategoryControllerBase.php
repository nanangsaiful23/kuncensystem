<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    /**
     * Cegah kategori dobel: kode dan nama (tanpa beda huruf besar/kecil)
     * tidak boleh sama dengan kategori lain yang masih aktif.
     */
    private function validateCategory(Request $request, $ignore_id = null)
    {
        $request->merge(['name' => trim(preg_replace('/\s+/', ' ', (string) $request->input('name')))]);

        $request->validate([
            'code' => ['required', Rule::unique('categories', 'code')->ignore($ignore_id)],
            'name' => ['required', function ($attribute, $value, $fail) use ($ignore_id) {
                $exists = Category::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($value)])
                                  ->when($ignore_id, fn ($q) => $q->where('id', '!=', $ignore_id))
                                  ->exists();
                if ($exists) $fail('Kategori dengan nama "' . $value . '" sudah ada.');
            }],
            'main_category_id' => ['required', Rule::exists('main_categories', 'id')->whereNull('deleted_at')],
        ], [
            'code.unique'               => 'Kode kategori sudah dipakai.',
            'main_category_id.required' => 'Area wajib dipilih.',
        ]);
    }

    public function storeCategoryBase(Request $request)
    {
        $this->validateCategory($request);

        $data = $request->input();

        $category = Category::create($data);

        return $category;
    }

    public function updateCategoryBase($category_id, Request $request)
    {
        $this->validateCategory($request, $category_id);

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
