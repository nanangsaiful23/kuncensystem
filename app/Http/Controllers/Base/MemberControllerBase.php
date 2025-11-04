<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Member;
use App\Models\PiutangPayment;
use App\Models\Transaction;

trait MemberControllerBase 
{
    public function indexMemberBase($start_date, $end_date, $sort, $order, $pagination)
    {
        if($pagination == 'all')
           $members = Member::leftjoin('transactions', 'transactions.member_id', 'members.id')
                            ->select('members.id', 'members.name', 'members.address', 'members.phone_number', DB::raw('SUM(transactions.total_sum_price) as total_sum_price'), DB::raw('COUNT(transactions.id) as total_transaction'))
                            ->whereRaw('(transactions.type = "normal" OR transactions.type = "retur")')
                            ->whereDate('transactions.created_at', '>=', $start_date)
                            ->whereDate('transactions.created_at', '<=', $end_date) 
                            ->where('members.id', '!=', '1')
                            ->groupBy('members.id', 'members.name', 'members.address', 'members.phone_number')
                            ->orderBy($sort, $order)->get();
        else
           $members = Member::leftjoin('transactions', 'transactions.member_id', 'members.id')
                            ->select('members.id', 'members.name', 'members.address', 'members.phone_number', DB::raw('SUM(transactions.total_sum_price) as total_sum_price'), DB::raw('COUNT(transactions.id) as total_transaction'))
                            ->whereRaw('(transactions.type = "normal" OR transactions.type = "retur")')
                            ->whereDate('transactions.created_at', '>=', $start_date)
                            ->whereDate('transactions.created_at', '<=', $end_date) 
                            ->where('members.id', '!=', '1')
                            ->groupBy('members.id', 'members.name', 'members.address', 'members.phone_number')
                            ->orderBy($sort, $order)->paginate($pagination);

        return $members;
    }

    public function searchByNameMemberBase($query)
    {
        $members = Member::where('name', 'like', '%'. $query . '%')
                         ->orderBy('name', 'asc')
                         ->get();

        foreach($members as $member)
        {
            $member->transaction = showRupiah($member->totalTransactionNormal()->sum('total_sum_price'));
            $member->payment     = showRupiah($member->totalPayment()->sum('money') + $member->totalTransactionCash()->sum('total_sum_price'));
            $member->credit      = showRupiah($member->totalTransactionNormal()->sum('total_sum_price') - ($member->totalPayment()->sum('money') + $member->totalTransactionCash()->sum('total_sum_price')));
        }

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
                                                ->whereRaw('(type = "normal" OR type = "retur") AND payment = "cash" AND money_paid >= total_sum_price AND member_id = ' . $member_id)
                                                ->orderBy('transactions.created_at','desc')
                                                ->get();

            $transactions['credit'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date) 
                                                ->whereRaw('(type = "normal" OR type = "retur") AND payment = "cash" AND money_paid < total_sum_price AND member_id = ' . $member_id)
                                                ->orderBy('transactions.created_at','desc')
                                                ->get();

            $transactions['transfer'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date) 
                                                ->whereRaw('(type = "normal" OR type = "retur") AND payment = "transfer" AND member_id = ' . $member_id)
                                                ->orderBy('transactions.created_at','desc')
                                                ->get();
        }
        else
        {
            $transactions['cash'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date)
                                                ->whereRaw('(type = "normal" OR type = "retur") AND payment = "cash" AND money_paid >= total_sum_price AND member_id = ' . $member_id)
                                                ->orderBy('transactions.created_at','desc')
                                                ->paginate($pagination);

            $transactions['credit'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date)
                                                ->whereRaw('(type = "normal" OR type = "retur") AND payment = "cash" AND money_paid < total_sum_price AND member_id = ' . $member_id)
                                                ->orderBy('transactions.created_at','desc')
                                                ->paginate($pagination);
                                                
            $transactions['transfer'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date)
                                                ->whereRaw('(type = "normal" OR type = "retur") AND payment = "transfer" AND member_id = ' . $member_id)
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
