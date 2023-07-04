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

        $goods = Good::where('name', 'like', '%' . $query . '%')
                     ->orWhere('code', 'like', '%' . $query . '%')
                     ->orderBy('name', 'asc')
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