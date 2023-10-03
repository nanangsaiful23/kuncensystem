<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Member extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'name', 'address', 'phone_number'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];

    public function totalTransaction()
    {
        $transactions = Transaction::where('member_id', $this->id)
                                    ->get();

        return $transactions;
    }

    public function totalPayment()
    {
        $payments = PiutangPayment::where('member_id', $this->id)
                                      ->get();

        return $payments;
    }

    public function getAllRecords()
    {
        $dates = [];
        $transactions = Transaction::select(DB::raw('DISTINCT(DATE(transactions.created_at)) as date'))
                                   ->where('transactions.member_id', $this->id)
                                   // ->distinct('transactions.created_at')
                                   ->get();
                                   // dd($transactions);die;
        foreach($transactions as $transaction)
        {
            if(!in_array($transaction->date, $dates))
            {
                array_push($dates, $transaction->date);
            }
        }

        $payments = PiutangPayment::select(DB::raw('DISTINCT(DATE(piutang_payments.created_at)) as date'))
                                   ->where('piutang_payments.member_id', $this->id)
                                   ->get();
        foreach($payments as $payment)
        {
            if(!in_array($payment->date, $dates))
            {
                array_push($dates, $payment->date);
            }
        }

        // $records = Transaction::join('piutang_payments', 'piutang_payments.member_id', 'transactions.member_id')
        //                       ->select('transactions.id as tid', 'transactions.created_at as tca', 'transactions.total_sum_price as ttotal', 'piutang_payments.id as pid', 'piutang_payments.created_at as pca', 'piutang_payments.money as ptotal')
        //                       ->where('transactions.member_id', $this->id)
        //                       ->where('piutang_payments.member_id', $this->id)
        //                       ->get();
        // dd($records);die;

        // $data = [];
        // foreach($dates as $date)
        // {
        //     $dates->transaction = Transaction::select(DB::raw('SUM(transactions.total_sum_price) as total'))
        //                                      ->whereDate('transactions.created_at', $date)
        //                                      ->
        // }
        return $dates;
    }
}
