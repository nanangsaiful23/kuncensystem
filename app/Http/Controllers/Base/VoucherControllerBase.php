<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\Transaction;
use App\Models\Voucher;

trait VoucherControllerBase 
{
    public function indexVoucherBase($pagination)
    {
        if($pagination == 'all')
           $vouchers = Voucher::orderBy('name', 'asc')->get();
        else
           $vouchers = Voucher::orderBy('name', 'asc')->paginate($pagination);

        return $vouchers;
    }

    public function searchByCodeVoucherBase($code)
    {
        $voucher = Voucher::where('code', $code)
                       ->whereDate('start_period', '<=', date('Y-m-d'))
                       ->whereDate('end_period', '>=', date('Y-m-d'))
                       ->where('is_valid', 1)
                       ->first();

        $transactions = Transaction::where('voucher', $code)->get();

        if($voucher != null)
            if($transactions->count() < $voucher->quota)
            {
                $code = 'valid';
                return [$voucher, $code];
            }
            else
            {
                $code = 'Kuota voucher habis';
                return [null, $code];
            }
        else 
        {
            $code = 'Voucher tidak valid';
            return [null, $code];
        }
    }

    public function storeVoucherBase(Request $request)
    {
        $data = $request->input();

        $voucher = Voucher::create($data);

        return $voucher;
    }

    public function updateVoucherBase($voucher_id, Request $request)
    {
        $data = $request->input();

        $voucher = Voucher::find($voucher_id);
        $voucher->update($data);

        return $voucher;
    }

    public function deleteVoucherBase($voucher_id)
    {
        $voucher = Voucher::find($voucher_id);
        $voucher->delete();
    }
}
