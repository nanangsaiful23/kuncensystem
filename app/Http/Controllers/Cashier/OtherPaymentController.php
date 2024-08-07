<?php

namespace App\Http\Controllers\Cashier;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\OtherPaymentControllerBase;

use App\Models\Account;
use App\Models\OtherPayment;

class OtherPaymentController extends Controller
{
    use OtherPaymentControllerBase;

    public function __construct()
    {
        $this->middleware('cashier');
    }

    public function index($start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Biaya Lain';
        $default['page'] = 'other-payment';
        $default['section'] = 'all';

        $other_payments = $this->indexOtherPaymentBase($start_date, $end_date, $pagination);

        return view('cashier.layout.page', compact('default', 'other_payments', 'start_date', 'end_date', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah Biaya Lain';
        $default['page'] = 'other-payment';
        $default['section'] = 'create';

        return view('cashier.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $this->storeOtherPaymentBase($request);

        session(['alert' => 'add', 'data' => 'Biaya Lain']);

        return redirect('/cashier/other-payment/' . date('Y-m-d') . '/' . date('Y-m-d') . '/15');
    }
}
