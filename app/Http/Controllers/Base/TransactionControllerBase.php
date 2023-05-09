<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\Account;
use App\Models\Journal;
use App\Models\Transaction;
use App\Models\TransactionDetail;

trait TransactionControllerBase 
{
    public function indexTransactionBase($start_date, $end_date, $pagination)
    {
        $transactions = [];

        if($pagination == 'all')
        {
            $transactions['cash'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date) 
                                                ->where('payment', 'cash')
                                                ->where('money_paid', '>', 0)
                                                ->orderBy('transactions.created_at','desc')
                                                ->get();

            $transactions['credit'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date) 
                                                ->where('payment', 'cash')
                                                ->where('money_paid', '<=', 0)
                                                ->orderBy('transactions.created_at','desc')
                                                ->get();

            $transactions['transfer'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date) 
                                                ->where('payment', 'transfer')
                                                ->orderBy('transactions.created_at','desc')
                                                ->get();
        }
        else
        {
            $transactions['cash'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date)
                                                ->where('payment', 'cash')
                                                ->where('money_paid', '>', 0)
                                                ->orderBy('transactions.created_at','desc')
                                                ->paginate($pagination);

            $transactions['credit'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date)
                                                ->where('payment', 'cash')
                                                ->where('money_paid', '<=', 0)
                                                ->orderBy('transactions.created_at','desc')
                                                ->paginate($pagination);
                                                
            $transactions['transfer'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date)
                                                ->where('payment', 'transfer')
                                                ->orderBy('transactions.created_at','desc')
                                                ->paginate($pagination);
        }

        return $transactions;
    }

    public function storeTransactionBase($role, $role_id, Request $request)
    {
        $hpp = 0;
        #tabel transaction
        $data_transaction['type'] = $request->type;
        $data_transaction['role'] = $role;
        $data_transaction['role_id'] = $role_id;
        $data_transaction['member_id'] = $request->member_id;
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

        for ($i = 0; $i < sizeof($request->names); $i++) 
        { 
            if($request->names[$i] != null)
            {
                $data_detail['good_id'] = $request->names[$i];
                $data_detail['quantity'] = $request->quantities[$i];
                $data_detail['buy_price'] = unformatNumber($request->buy_prices[$i]);
                $data_detail['selling_price'] = unformatNumber($request->prices[$i]);
                $data_detail['discount_price'] = unformatNumber($request->discounts[$i]);
                $data_detail['sum_price'] = unformatNumber($request->sums[$i]);

                TransactionDetail::create($data_detail);

                $hpp += $data_detail['buy_price'] * $data_detail['quantity'];
            }
        }

        #tabel journal transaksi
        $journal = Journal::whereDate('journal_date', date('Y-m-d'))->where('type', 'transaction')->first();

        if($journal != null)
        {
            $data_journal['debit'] = floatval($journal->debit) + floatval($data_transaction['total_sum_price']);
            $data_journal['credit'] = floatval($journal->credit) + floatval($data_transaction['total_sum_price']);

            $journal->update($data_journal);
        }
        else
        {
            $data_journal['type']               = 'transaction';
            $data_journal['journal_date']       = date('Y-m-d');
            $data_journal['name']               = 'Penjualan tanggal ' . displayDate(date('Y-m-d'));
            $data_journal['debit_account_id']   = Account::where('code', '1111')->first()->id;
            $data_journal['debit']              = $data_transaction['total_sum_price'];
            $data_journal['credit_account_id']  = Account::where('code', '4101')->first()->id;
            $data_journal['credit']             = $data_transaction['total_sum_price'];

            Journal::create($data_journal);
        }

        #tabel journal hpp
        $hpp_journal = Journal::whereDate('journal_date', date('Y-m-d'))->where('type', 'hpp')->first();

        if($hpp_journal != null)
        {
            $data_hpp['debit'] = floatval($hpp_journal->debit) + floatval($hpp);
            $data_hpp['credit'] = floatval($hpp_journal->credit) + floatval($hpp);

            $hpp_journal->update($data_hpp);
        }
        else
        {
            $data_hpp['type']               = 'hpp';
            $data_hpp['journal_date']       = date('Y-m-d');
            $data_hpp['name']               = 'Penjualan tanggal ' . displayDate(date('Y-m-d'));
            $data_hpp['debit_account_id']   = Account::where('code', '5101')->first()->id;
            $data_hpp['debit']              = $hpp;
            $data_hpp['credit_account_id']  = Account::where('code', '1141')->first()->id;
            $data_hpp['credit']             = $hpp;

            Journal::create($data_hpp);
        }

        #tabel journal piutang
        if($data_transaction['money_paid'] <= 0)
        {
            $piutang = Journal::whereDate('journal_date', date('Y-m-d'))->where('type', 'piutang')->first();

            if($piutang != null)
            {
                $data_piutang['debit'] = floatval($piutang->debit) + floatval($data_transaction['total_sum_price']);
                $data_piutang['credit'] = floatval($piutang->credit) + floatval($data_transaction['total_sum_price']);

                $piutang->update($data_piutang);
            }
            else
            {
                $data_piutang['type']               = 'piutang';
                $data_piutang['journal_date']       = date('Y-m-d');
                $data_piutang['name']               = 'Piutang dagang member ' . $transaction->member->name . ' (ID ' . $transaction->member->id . ')';
                $data_piutang['debit_account_id']   = Account::where('code', '1131')->first()->id;
                $data_piutang['debit']              = $data_transaction['total_sum_price'];
                $data_piutang['credit_account_id']  = Account::where('code', '1111')->first()->id;
                $data_piutang['credit']             = $data_transaction['total_sum_price'];

                Journal::create($data_piutang);
            }
        }

        #tabel journal transfer
        if($request->payment == 'transfer')
        {
            $transfer = Journal::whereDate('journal_date', date('Y-m-d'))->where('type', 'transfer')->first();

            if($transfer != null)
            {
                $data_transfer['debit'] = floatval($transfer->debit) + floatval($data_transaction['total_sum_price']);
                $data_transfer['credit'] = floatval($transfer->credit) + floatval($data_transaction['total_sum_price']);

                $transfer->update($data_transfer);
            }
            else
            {
                $data_transfer['type']               = 'transfer';
                $data_transfer['journal_date']       = date('Y-m-d');
                $data_transfer['name']               = 'Transfer transaksi ID ' . $transaction->id;
                $data_transfer['debit_account_id']   = Account::where('code', '1112')->first()->id;
                $data_transfer['debit']              = $data_transaction['total_sum_price'];
                $data_transfer['credit_account_id']  = Account::where('code', '1111')->first()->id;
                $data_transfer['credit']             = $data_transaction['total_sum_price'];

                Journal::create($data_transfer);
            }
        }

        return $transaction;
    }

}