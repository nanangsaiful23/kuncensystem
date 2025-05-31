<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Member extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'name', 'address', 'phone_number', 'store_name', 'store_address'
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

    public function totalTransactionNormal()
    {
        $transactions = Transaction::whereRaw('(type = "normal" OR type = "retur") AND member_id = ' . $this->id)
                                    ->get();
                                    // dd($transactions);die;

        return $transactions;
    }

    public function totalTransactionCash()
    {
        $transactions = Transaction::whereRaw('(type = "normal" OR type = "retur") AND money_paid >= total_sum_price AND payment = "cash" AND member_id = ' . $this->id)
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

    public function getGoodRecords()
    {
        $goods = TransactionDetail::join('transactions', 'transactions.id', 'transaction_details.transaction_id')
                                  ->join('good_units', 'good_units.id', 'transaction_details.good_unit_id')
                                  ->join('units', 'units.id', 'good_units.unit_id')
                                  ->join('goods', 'goods.id', 'good_units.good_id')
                                  ->select('goods.name as good_name', 'units.name as unit_name', DB::raw('SUM(transaction_details.quantity) as total'))
                                  ->where('transactions.member_id', $this->id)
                                  ->groupBy('goods.name', 'units.name')
                                  ->orderBy('total', 'desc')
                                  ->get();

        return $goods;
    }

    public function lastTransaction()
    {
        return Transaction::whereRaw('(type = "normal" OR type = "retur") AND member_id = ' . $this->id)
                          ->orderBy('id', 'desc')
                          ->first();
    }
}
