<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\Account;
use App\Models\Journal;
use App\Models\PiutangPayment;

trait OtherTransactionControllerBase 
{
    public function indexOtherTransactionBase($start_date, $end_date, $pagination)
    {
        if($pagination == 'all')
           $other_transactions = Journal::where('type', 'like', '%_transaction')
                                        ->whereDate('journals.journal_date', '>=', $start_date)
                                        ->whereDate('journals.journal_date', '<=', $end_date) 
                                        ->get();
        else
           $other_transactions = Journal::where('type', 'like', '%_transaction')
                                        ->whereDate('journals.journal_date', '>=', $start_date)
                                        ->whereDate('journals.journal_date', '<=', $end_date) 
                                        ->paginate($pagination);

        return $other_transactions;
    }

    public function storeOtherTransactionBase(Request $request)
    {
        $request->money = unformatNumber($request->money);

        if($request->type == 'box_transaction')
        {
            $data_box['type']               = 'box_transaction';
            $data_box['journal_date']       = date('Y-m-d');
            $data_box['name']               = 'Penjualan kardus';
            $data_box['debit_account_id']   = Account::where('code', '1111')->first()->id;
            $data_box['debit']              = $request->money;
            $data_box['credit_account_id']  = Account::where('code', '6101')->first()->id;
            $data_box['credit']             = $request->money;

            $journal = Journal::create($data_box);
        }

        if($request->type == 'piutang_transaction')
        {
            $data_member['member_id']    = $request->member_id;
            $data_member['payment_date'] = date('Y-m-d');
            $data_member['money']        = $request->money;

            $payment = PiutangPayment::create($data_member);

            if($request->payment == 'cash')
            {
                $data_piutang['debit_account_id']   = Account::where('code', '1111')->first()->id;
            }
            elseif($request->payment == 'transfer')
            {
                $data_piutang['debit_account_id']   = Account::where('code', '1112')->first()->id;
            }

            $data_piutang['type']               = 'piutang_transaction';
            $data_piutang['journal_date']       = date('Y-m-d');
            $data_piutang['name']               = 'Pembayaran ' . $request->payment . ' piutang member ' . $payment->member->name . ' (ID member ' . $payment->member->id . ')';
            $data_piutang['debit']              = $request->money;
            $data_piutang['credit_account_id']  = Account::where('code', '1131')->first()->id;
            $data_piutang['credit']             = $request->money;

            $journal = Journal::create($data_piutang);
        }

        if($request->type == 'pulsa_transaction')
        {
            $request->buy_price = unformatNumber($request->buy_price);

            if($request->payment == 'cash')
            {
                $data_pulsa_transaction['debit_account_id']   = Account::where('code', '1111')->first()->id;
            }
            elseif($request->payment == 'transfer')
            {
                $data_pulsa_transaction['debit_account_id']   = Account::where('code', '1112')->first()->id;
            }

            $data_pulsa_transaction['type']               = 'pulsa_transaction';
            $data_pulsa_transaction['journal_date']       = date('Y-m-d');
            $data_pulsa_transaction['name']               = 'Pembayaran ' . $request->payment . ' pulsa ('. $request->no_token . ')';
            $data_pulsa_transaction['debit']              = $request->money;
            $data_pulsa_transaction['credit_account_id']  = Account::where('code', '4101')->first()->id;
            $data_pulsa_transaction['credit']             = $request->money;

            $journal = Journal::create($data_pulsa_transaction);
            
            $data_pulsa_hpp['type']               = 'pulsa_transaction_hpp';
            $data_pulsa_hpp['journal_date']       = date('Y-m-d');
            $data_pulsa_hpp['name']               = 'Pembayaran ' . $request->payment . ' pulsa hpp ('. $request->no_token . ')';
            $data_pulsa_hpp['debit_account_id']   = Account::where('code', '5101')->first()->id;
            $data_pulsa_hpp['debit']              = $request->buy_price;
            $data_pulsa_hpp['credit_account_id']  = Account::where('code', '1112')->first()->id;
            $data_pulsa_hpp['credit']             = $request->buy_price;

            Journal::create($data_pulsa_hpp);
        }

        return $journal;
    }
}
