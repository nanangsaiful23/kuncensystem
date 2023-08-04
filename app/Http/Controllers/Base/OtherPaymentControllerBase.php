<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\Account;
use App\Models\Journal;

trait OtherPaymentControllerBase 
{
    public function indexOtherPaymentBase($start_date, $end_date, $pagination)
    {
        if($pagination == 'all')
           $other_payments = Journal::where('type', 'like', '%_payment%')
                                        ->whereDate('journals.journal_date', '>=', $start_date)
                                        ->whereDate('journals.journal_date', '<=', $end_date) 
                                        ->get();
        else
           $other_payments = Journal::where('type', 'like', '%_payment%')
                                        ->whereDate('journals.journal_date', '>=', $start_date)
                                        ->whereDate('journals.journal_date', '<=', $end_date) 
                                        ->paginate($pagination);

        return $other_payments;
    }

    public function storeOtherPaymentBase(Request $request)
    {
        $request->money = unformatNumber($request->money);
        
        if($request->payment == 'cash')
        {
            $data_payment['credit_account_id']   = Account::where('code', '1111')->first()->id;
        }
        elseif($request->payment == 'transfer')
        {
            $data_payment['credit_account_id']   = Account::where('code', '1112')->first()->id;
        }

        $account = Account::find($request->debit_account_id);

        if($account == null)
        {
            $account = Account::where('code', $request->debit_account_id)->first();
        }

        // $payment = Journal::whereDate('journal_date', date('Y-m-d'))->where('debit_account_id', $request->debit_account_id)->where('credit_account_id', $data_payment['credit_account_id'])->first();

        // if($payment != null)
        // {
        //     $data_payment['debit'] = floatval($payment->debit) + floatval($request->money);
        //     $data_payment['credit'] = floatval($payment->credit) + floatval($request->money);

        //     $payment->update($data_payment);
        // }
        // else
        // {
            $data_payment['type']               = 'other_payment';
            $data_payment['journal_date']       = date('Y-m-d');
            $data_payment['name']               = $account->name . ' ' . $request->notes . ' (' . $request->payment . ')';
            $data_payment['debit_account_id']   = $account->id;
            $data_payment['debit']              = $request->money;
            $data_payment['credit']             = $request->money;

            Journal::create($data_payment);
        // }

        return true;
    }
}
