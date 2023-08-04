<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\Account;
use App\Models\Distributor;
use App\Models\GoodUnit;
use App\Models\Journal;
use App\Models\Member;
use App\Models\PiutangPayment;
use App\Models\Transaction;
use App\Models\TransactionDetail;

trait InternalTransactionControllerBase 
{
    public function indexInternalTransactionBase($role, $role_id, $start_date, $end_date, $pagination)
    {
        if($pagination == 'all')
        {
            if($role_id == 'all')
            {
                $transactions = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                            ->whereDate('transactions.created_at', '<=', $end_date) 
                                            ->where('type', '!=', 'normal')
                                            ->where('type', '!=', 'retur')
                                            ->where('type', '!=', 'not valid')
                                            ->orderBy('transactions.created_at','desc')
                                            ->get();
            }
            else
            {
                $transactions = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                            ->whereDate('transactions.created_at', '<=', $end_date) 
                                            ->where('type', '!=', 'normal')
                                            ->where('type', '!=', 'retur')
                                            ->where('type', '!=', 'not valid')
                                            ->where('role', $role)
                                            ->where('role_id', $role_id)
                                            ->orderBy('transactions.created_at','desc')
                                            ->get();
            }
        }
        else
        {
            if($role_id == 'all')
            {
                $transactions = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                            ->whereDate('transactions.created_at', '<=', $end_date)
                                            ->where('type', '!=', 'normal')
                                            ->where('type', '!=', 'retur')
                                            ->where('type', '!=', 'not valid')
                                            ->orderBy('transactions.created_at','desc')
                                            ->paginate($pagination);
            }
            else
            {
                $transactions = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                            ->whereDate('transactions.created_at', '<=', $end_date)
                                            ->where('role', $role)
                                            ->where('role_id', $role_id)
                                            ->where('type', '!=', 'normal')  
                                            ->where('type', '!=', 'retur') 
                                            ->where('type', '!=', 'not valid')
                                            ->orderBy('transactions.created_at','desc')
                                            ->paginate($pagination);
            }
        }

        return $transactions;
    }

    public function storeInternalTransactionBase($role, $role_id, Request $request)
    {
        $hpp = 0;

        #tabel transaction
        $data_transaction['type'] = $request->type;
        $data_transaction['role'] = $role;
        $data_transaction['role_id'] = $role_id;
        $data_transaction['member_id'] = 1;
        $data_transaction['total_item_price'] = unformatNumber($request->total_item_price);
        $data_transaction['total_discount_price'] = unformatNumber($request->total_discount_price);
        $data_transaction['total_sum_price'] = unformatNumber($request->total_sum_price);
        $data_transaction['money_paid'] = unformatNumber($request->money_paid);
        $data_transaction['money_returned'] = unformatNumber($request->money_returned);
        $data_transaction['store']   = 'kuncen';
        $data_transaction['payment'] = $request->payment;
        $data_transaction['note']    = $request->note;

        $transaction = Transaction::create($data_transaction);

        #tabel transaction detail
        $data_detail['transaction_id'] = $transaction->id;

        for ($i = 0; $i < sizeof($request->barcodes); $i++) 
        { 
            if($request->barcodes[$i] != null)
            {
                $good_unit = GoodUnit::find($request->barcodes[$i]);
                $data_detail['good_unit_id']   = $good_unit->id;
                $data_detail['type']           = $request->type;
                $data_detail['quantity']       = $request->quantities[$i];
                $data_detail['real_quantity']  = $request->quantities[$i] * $good_unit->unit->quantity;
                $data_detail['buy_price'] = unformatNumber($request->buy_prices[$i]);
                $data_detail['selling_price'] = unformatNumber($request->prices[$i]);
                $data_detail['discount_price'] = unformatNumber($request->discounts[$i]);
                $data_detail['sum_price'] = unformatNumber($request->sums[$i]);

                TransactionDetail::create($data_detail);

                $hpp += $data_detail['buy_price'] * $data_detail['quantity'];
            }
        }

        #tabel journal transaksi
        // $journal = Journal::whereDate('journal_date', date('Y-m-d'))->where('type', 'transaction')->first();

        // if($journal != null)
        // {
        //     $data_journal['debit'] = floatval($journal->debit) + floatval($data_transaction['total_sum_price']);
        //     $data_journal['credit'] = floatval($journal->credit) + floatval($data_transaction['total_sum_price']);

        //     $journal->update($data_journal);
        // }
        // else
        // {
        //     $data_journal['type']               = 'transaction';
        //     $data_journal['journal_date']       = date('Y-m-d');
        //     $data_journal['name']               = 'Penjualan tanggal ' . displayDate(date('Y-m-d'));
        //     $data_journal['debit_account_id']   = Account::where('code', '1111')->first()->id;
        //     $data_journal['debit']              = $data_transaction['total_sum_price'];
        //     $data_journal['credit_account_id']  = Account::where('code', '4101')->first()->id;
        //     $data_journal['credit']             = $data_transaction['total_sum_price'];

        //     Journal::create($data_journal);
        // }

        #tabel journal hpp
        // $hpp_journal = Journal::whereDate('journal_date', date('Y-m-d'))->where('type', 'hpp')->first();

        // if($hpp_journal != null)
        // {
        //     $data_hpp['debit'] = floatval($hpp_journal->debit) + floatval($hpp);
        //     $data_hpp['credit'] = floatval($hpp_journal->credit) + floatval($hpp);

        //     $hpp_journal->update($data_hpp);
        // }
        // else
        // {
        //     $data_hpp['type']               = 'hpp';
        //     $data_hpp['journal_date']       = date('Y-m-d');
        //     $data_hpp['name']               = 'Penjualan tanggal ' . displayDate(date('Y-m-d'));
        //     $data_hpp['debit_account_id']   = Account::where('code', '5101')->first()->id;
        //     $data_hpp['debit']              = $hpp;
        //     $data_hpp['credit_account_id']  = Account::where('code', '1141')->first()->id;
        //     $data_hpp['credit']             = $hpp;

        //     Journal::create($data_hpp);
        // }

        #journal penyusutan barang
        if($request->type == '5215')
        {
            $data_pb['type']               = 'penyusutan';
            $data_pb['journal_date']       = date('Y-m-d');
            $data_pb['name']               = 'Barang hilang (ID transaksi ' . $transaction->id . ')';
            $data_pb['debit_account_id']   = Account::where('code', '5215')->first()->id;
            $data_pb['debit']              = $data_transaction['total_sum_price'];
            $data_pb['credit_account_id']  = Account::where('code', '1141')->first()->id;
            $data_pb['credit']             = $data_transaction['total_sum_price'];

            Journal::create($data_pb);
        }

        #journal operasional toko
        if($request->type == '5220')
        {
            $data_op['type']               = 'operasional';
            $data_op['journal_date']       = date('Y-m-d');
            $data_op['name']               = 'Biaya operasional toko (ID transaksi' . $transaction->id . ')';
            $data_op['debit_account_id']   = Account::where('code', '5220')->first()->id;
            $data_op['debit']              = $data_transaction['total_sum_price'];
            $data_op['credit_account_id']  = Account::where('code', '1141')->first()->id;
            $data_op['credit']             = $data_transaction['total_sum_price'];

            Journal::create($data_op);
        }

        #journal hutang dagang
        if($request->type == '2101')
        {
            $distributor = Distributor::find($request->distributor_id);

            $data_ud['type']               = 'hutang dagang ' . $distributor->id;
            $data_ud['journal_date']       = date('Y-m-d');
            $data_ud['name']               = 'Hutang dagang distributor ' . $distributor->name . ' (ID transaksi ' . $transaction->id . ')';
            $data_ud['debit_account_id']   = Account::where('code', '2101')->first()->id;
            $data_ud['debit']              = $data_transaction['total_sum_price'];
            $data_ud['credit_account_id']  = Account::where('code', '1141')->first()->id;
            $data_ud['credit']             = $data_transaction['total_sum_price'];

            Journal::create($data_ud);
        }

        return $transaction;
    }

}