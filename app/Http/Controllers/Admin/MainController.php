<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

use App\Models\Account;
use App\Models\Journal;

class MainController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index()
    {
        $default['page_name'] = 'Admin Home';

        return view('admin.index', compact('default'));
    }

    public function scale()
    {
        $penjualan_account = Account::where('code', '4101')->first();
        $penjualan = Journal::where('credit_account_id', $penjualan_account->id)
                            ->get();

        $hpp_account = Account::where('code', '5101')->first();
        $hpp = Journal::where('debit_account_id', $hpp_account->id)
                      ->get();

        $payments = Journal::select(DB::raw('SUM(journals.debit) as debit'), 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->rightJoin('accounts', 'accounts.id', 'journals.debit_account_id')
                        ->where('accounts.code', 'like', '52%')
                        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.balance')
                        ->get();

        return view('admin.scale', compact('penjualan_account', 'penjualan', 'hpp_account', 'hpp', 'payments'));
    }
}