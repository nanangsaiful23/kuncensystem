<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Models\Type;

trait TypeControllerBase 
{
    public function indexTypeBase($sort, $order, $pagination)
    {
        if($pagination == 'all')
           $types = Type::orderBy($sort, $order)->get();
        else
           $types = Type::orderBy($sort, $order)->paginate($pagination);

        return $types;
    }

    public function searchTypeBase($keyword)
    {
        $types = Type::where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('code', 'like', '%' . $keyword . '%')
                    ->get();

        return $types;
    }

    /**
     * Cegah jenis dobel: nama jenis tidak boleh sama dengan jenis lain
     * di kategori induk yang sama, dan kode tidak boleh dipakai dua kali.
     */
    private function validateType(Request $request, $ignore_id = null)
    {
        $request->merge(['name' => trim(preg_replace('/\s+/', ' ', (string) $request->input('name')))]);

        $request->validate([
            'code' => ['nullable', Rule::unique('types', 'code')->ignore($ignore_id)->whereNull('deleted_at')],
            'category_id' => ['required', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'name' => ['required', function ($attribute, $value, $fail) use ($request, $ignore_id) {
                $exists = Type::where('category_id', $request->input('category_id'))
                              ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($value)])
                              ->when($ignore_id, fn ($q) => $q->where('id', '!=', $ignore_id))
                              ->exists();
                if ($exists) $fail('Jenis "' . $value . '" sudah ada di kategori ini.');
            }],
        ], [
            'code.unique'          => 'Kode jenis sudah dipakai.',
            'category_id.required' => 'Kategori induk wajib dipilih.',
        ]);
    }

    public function storeTypeBase(Request $request)
    {
        $this->validateType($request);

        $data = $request->input();

        $type = Type::create($data);

        return $type;
    }

    public function updateTypeBase($type_id, Request $request)
    {
        $this->validateType($request, $type_id);

        $data = $request->input();

        $type = Type::find($type_id);
        $type->update($data);

        return $type;
    }

    public function deleteTypeBase($type_id)
    {
        $type = Type::find($type_id);
        $type->delete();
    }
}
