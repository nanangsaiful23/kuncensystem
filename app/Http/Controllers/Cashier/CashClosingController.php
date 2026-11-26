<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Base\CashClosingControllerBase;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/** Simpan Tutup Kasir; formnya ada di Resume Transaksi Total. */
class CashClosingController extends Controller
{
    use CashClosingControllerBase;

    public function __construct()
    {
        $this->middleware('cashier');
    }

    public function store(Request $request)
    {
        return $this->storeBase($request, 'cashier');
    }
}
