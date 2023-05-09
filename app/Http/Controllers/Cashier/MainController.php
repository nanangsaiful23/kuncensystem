<?php

namespace App\Http\Controllers\Cashier;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MainController extends Controller
{
    public function __construct()
    {
        $this->middleware('cashier');
    }

    public function index()
    {
        $default['page_name'] = 'Cashier Home';

        return view('cashier.index', compact('default'));
    }
}