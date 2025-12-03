<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

use App\Models\Good;

class MainController extends Controller
{
    public function search($query)
    {
        $default['page_name'] = 'CARI BARANG';

        $goods = Good::leftjoin('types', 'goods.type_id', 'types.id')
                     ->select('goods.*')
                     // ->whereRaw("goods.code like ? OR goods.name like ? OR types.name like ? ", ['%'. $query . '%', '%'. $query . '%', '%'. $query . '%'])
                     ->where('goods.code', 'like', '%'. $query . '%')
                     ->orWhere('goods.name', 'like', '%'. $query . '%')
                     ->orWhere('types.name', 'like', '%'. $query . '%')
                     ->where('goods.deleted_at', '=', null)
                     ->orderBy('goods.name')
                     ->get();
        
        return view('layout.good-search', compact('default', 'goods', 'query'));
    }

    public function getImage($directory, $url)
    {
        $path = Storage::disk('public')->getDriver()->getAdapter()->applyPathPrefix($directory . '/' . $url);

        if (!File::exists($path)) {
            abort(404);
        }

        $file = File::get($path);
        $type = File::mimeType($path);

        $response = Response::make($file, 200);
        $response->header("Content-Type", $type);

        return $response;
    }
}