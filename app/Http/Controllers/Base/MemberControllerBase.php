<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\Member;
use App\Models\PiutangPayment;
use App\Models\Transaction;

trait MemberControllerBase 
{
    public function indexMemberBase($pagination)
    {
        if($pagination == 'all')
           $members = Member::orderBy('name', 'asc')->get();
        else
           $members = Member::orderBy('name', 'asc')->paginate($pagination);

        return $members;
    }

    public function storeMemberBase(Request $request)
    {
        $data = $request->input();

        $member = Member::create($data);

        return $member;
    }

    public function updateMemberBase($member_id, Request $request)
    {
        $data = $request->input();

        $member = Member::find($member_id);
        $member->update($data);

        return $member;
    }

    public function deleteMemberBase($member_id)
    {
        $member = Member::find($member_id);
        $member->delete();
    }

    public function transactionMemberBase($member_id, $start_date, $end_date, $pagination)
    {
        $transactions = [];

        if($pagination == 'all')
        {
            $transactions['cash'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date) 
                                                ->where('payment', 'cash')
                                                ->whereRaw('money_paid >= total_sum_price')
                                                ->where('member_id', $member_id)
                                                ->orderBy('transactions.created_at','desc')
                                                ->get();

            $transactions['credit'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date) 
                                                ->where('payment', 'cash')
                                                ->whereRaw('money_paid < total_sum_price')
                                                ->where('member_id', $member_id)
                                                ->orderBy('transactions.created_at','desc')
                                                ->get();

            $transactions['transfer'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date) 
                                                ->where('payment', 'transfer')
                                                ->where('member_id', $member_id)
                                                ->orderBy('transactions.created_at','desc')
                                                ->get();
        }
        else
        {
            $transactions['cash'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date)
                                                ->where('payment', 'cash')
                                                ->whereRaw('money_paid >= total_sum_price')
                                                ->where('member_id', $member_id)
                                                ->orderBy('transactions.created_at','desc')
                                                ->paginate($pagination);

            $transactions['credit'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date)
                                                ->where('payment', 'cash')
                                                ->whereRaw('money_paid < total_sum_price')
                                                ->where('member_id', $member_id)
                                                ->orderBy('transactions.created_at','desc')
                                                ->paginate($pagination);
                                                
            $transactions['transfer'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date)
                                                ->where('payment', 'transfer')
                                                ->where('member_id', $member_id)
                                                ->orderBy('transactions.created_at','desc')
                                                ->paginate($pagination);
        }

        return $transactions;
    }

    public function paymentMemberBase($member_id, $start_date, $end_date, $pagination)
    {
        if($pagination == 'all')
        {
            $payments = PiutangPayment::whereDate('payment_date', '>=', $start_date)
                                        ->whereDate('payment_date', '<=', $end_date) 
                                        ->where('member_id', $member_id)
                                        ->get();
        }
        else
        {
            $payments = PiutangPayment::whereDate('payment_date', '>=', $start_date)
                                        ->whereDate('payment_date', '<=', $end_date) 
                                        ->where('member_id', $member_id)
                                        ->paginate($pagination);
        }

        return $payments;
    }
}
