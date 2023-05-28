<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

use App\Models\Good;

class MainController extends Controller
{
    public function search($query)
    {
        $default['page_name'] = 'CARI BARANG';

        $goods = Good::where('name', 'like', '%' . $query . '%')->get();

        return view('layout.good-search', compact('default', 'goods', 'query'));
    }
}