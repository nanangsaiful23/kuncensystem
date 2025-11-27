<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\Type;

trait TypeControllerBase 
{
    public function indexTypeBase($pagination)
    {
        if($pagination == 'all')
           $types = Type::orderBy('name', 'asc')->get();
        else
           $types = Type::orderBy('name', 'asc')->paginate($pagination);

        return $types;
    }

    public function storeTypeBase(Request $request)
    {
        $data = $request->input();

        $type = Type::create($data);

        return $type;
    }

    public function updateTypeBase($type_id, Request $request)
    {
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
