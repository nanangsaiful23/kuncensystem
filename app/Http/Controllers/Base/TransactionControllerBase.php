<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Models\Account;
use App\Models\Good;
use App\Models\GoodLoading;
use App\Models\GoodLoadingDetail;
use App\Models\GoodUnit;
use App\Models\Journal;
use App\Models\Member;
use App\Models\PiutangPayment;
use App\Models\ReturItem;
use App\Models\Transaction;
use App\Models\TransactionDetail;

trait TransactionControllerBase 
{
    public function indexTransactionBase($role, $role_id, $start_date, $end_date, $pagination)
    {
        if($role == 'all')
            $whereRole = '%%';
        else
            $whereRole = $role;

        if($role_id == 'all')
            $whereRoleId = '%%';
        else
            $whereRoleId = $role_id;

        $transactions = [];
        $all_normal = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                    ->whereDate('transactions.created_at', '<=', $end_date) 
                                    ->where('type', 'normal')
                                    ->whereRaw("coalesce(transactions.role, '') like ? AND coalesce(transactions.role_id, '') like ? ", array($whereRole, $whereRoleId))
                                    ->orderBy('transactions.created_at','desc')
                                    ->get();

        $all_retur = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                    ->whereDate('transactions.created_at', '<=', $end_date) 
                                    ->where('type', 'retur')
                                    ->whereRaw("coalesce(transactions.role, '') like ? AND coalesce(transactions.role_id, '') like ? ", array($whereRole, $whereRoleId))
                                    ->orderBy('transactions.created_at','desc')
                                    ->get();

        $hpp_normal = TransactionDetail::select(DB::raw('SUM(transaction_details.quantity * transaction_details.buy_price) AS total'))
                                        ->join('transactions', 'transactions.id', 'transaction_details.transaction_id')
                                       ->whereDate('transaction_details.created_at', '>=', $start_date)
                                        ->whereDate('transaction_details.created_at', '<=', $end_date) 
                                        ->where('transactions.type', 'normal')
                                        ->where('transaction_details.type', 'normal')
                                        ->get();

        $hpp_retur_normal = TransactionDetail::select(DB::raw('SUM(transaction_details.quantity * transaction_details.buy_price) AS total'))
                                        ->join('transactions', 'transactions.id', 'transaction_details.transaction_id')
                                       ->whereDate('transaction_details.created_at', '>=', $start_date)
                                        ->whereDate('transaction_details.created_at', '<=', $end_date) 
                                        ->where('transactions.type', 'retur')
                                        ->where('transaction_details.type', 'normal')
                                        ->get();

        $hpp_retur = TransactionDetail::select(DB::raw('SUM(transaction_details.quantity * transaction_details.buy_price) AS total'))
                                        ->join('transactions', 'transactions.id', 'transaction_details.transaction_id')
                                       ->whereDate('transaction_details.created_at', '>=', $start_date)
                                        ->whereDate('transaction_details.created_at', '<=', $end_date) 
                                        ->where('transactions.type', 'retur')
                                        ->where('transaction_details.type', 'retur')
                                        ->get();

        if($pagination == 'all')
        {
            $transactions['cash'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date) 
                                                ->where('payment', 'cash')
                                                ->whereRaw('money_paid >= total_sum_price')
                                                ->whereRaw("coalesce(transactions.role, '') like ? AND coalesce(transactions.role_id, '') like ? ", array($whereRole, $whereRoleId))
                                                ->where('type', 'normal')
                                                ->orderBy('transactions.created_at','desc')
                                                ->get();

            $transactions['credit'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date) 
                                                ->where('payment', 'cash')
                                                ->whereRaw('money_paid < total_sum_price')
                                                ->whereRaw("coalesce(transactions.role, '') like ? AND coalesce(transactions.role_id, '') like ? ", array($whereRole, $whereRoleId))
                                                ->where('type', 'normal')
                                                ->orderBy('transactions.created_at','desc')
                                                ->get();

            $transactions['transfer'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date) 
                                                ->where('payment', 'transfer')
                                                ->whereRaw('money_paid >= total_sum_price')
                                                ->whereRaw("coalesce(transactions.role, '') like ? AND coalesce(transactions.role_id, '') like ? ", array($whereRole, $whereRoleId))
                                                ->where('type', 'normal')
                                                ->orderBy('transactions.created_at','desc')
                                                ->get();

            $transactions['credit_transfer'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date) 
                                                ->where('payment', 'transfer')
                                                ->whereRaw('money_paid < total_sum_price')
                                                ->whereRaw("coalesce(transactions.role, '') like ? AND coalesce(transactions.role_id, '') like ? ", array($whereRole, $whereRoleId))
                                                ->where('type', 'normal')
                                                ->orderBy('transactions.created_at','desc')
                                                ->get();

            $transactions['retur'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date) 
                                                ->where('type', 'retur')
                                                ->whereRaw("coalesce(transactions.role, '') like ? AND coalesce(transactions.role_id, '') like ? ", array($whereRole, $whereRoleId))
                                                ->orderBy('transactions.created_at','desc')
                                                ->get();
        }
        else
        {
            $transactions['cash'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date)
                                                ->where('payment', 'cash')
                                                ->whereRaw('money_paid >= total_sum_price')
                                                ->whereRaw("coalesce(transactions.role, '') like ? AND coalesce(transactions.role_id, '') like ? ", array($whereRole, $whereRoleId))
                                                ->where('type', 'normal')
                                                ->orderBy('transactions.created_at','desc')
                                                ->paginate($pagination);

            $transactions['credit'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date)
                                                ->where('payment', 'cash')
                                                ->whereRaw('money_paid < total_sum_price')
                                                ->whereRaw("coalesce(transactions.role, '') like ? AND coalesce(transactions.role_id, '') like ? ", array($whereRole, $whereRoleId))
                                                ->where('type', 'normal')
                                                ->orderBy('transactions.created_at','desc')
                                                ->paginate($pagination);

            $transactions['transfer'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date) 
                                                ->where('payment', 'transfer')
                                                ->whereRaw('money_paid >= total_sum_price')
                                                ->whereRaw("coalesce(transactions.role, '') like ? AND coalesce(transactions.role_id, '') like ? ", array($whereRole, $whereRoleId))
                                                ->where('type', 'normal')
                                                ->orderBy('transactions.created_at','desc')
                                                ->paginate($pagination);

            $transactions['credit_transfer'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date) 
                                                ->where('payment', 'transfer')
                                                ->whereRaw('money_paid < total_sum_price')
                                                ->whereRaw("coalesce(transactions.role, '') like ? AND coalesce(transactions.role_id, '') like ? ", array($whereRole, $whereRoleId))
                                                ->where('type', 'normal')
                                                ->orderBy('transactions.created_at','desc')
                                                ->paginate($pagination);

            $transactions['retur'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                ->whereDate('transactions.created_at', '<=', $end_date) 
                                                ->where('type', 'retur')
                                                ->whereRaw("coalesce(transactions.role, '') like ? AND coalesce(transactions.role_id, '') like ? ", array($whereRole, $whereRoleId))
                                                ->orderBy('transactions.created_at','desc')
                                                ->paginate($pagination);
        }

        return [$transactions, $all_normal, $all_retur, $hpp_normal, $hpp_retur, $hpp_retur_normal];
    }

    /**
     * A form is opened with a submit_token. When the same form is posted
     * again (double click, or the cashier retries after the connection
     * dropped although the first post was saved) the first transaction is
     * returned instead of saving the sale twice.
     */
    private function previousSubmission(Request $request): ?Transaction
    {
        $token = $request->input('submit_token');

        if (! $token) {
            return null;
        }

        // Same-token requests run one after another. The lock is released
        // after the response, i.e. after AtomicRequest has committed.
        $lock = Cache::lock('submit-token:' . $token, 120);
        $lock->block(60);
        app()->terminating(fn () => $lock->release());

        $id = Cache::get('submit-token-done:' . $token);

        return $id ? Transaction::find($id) : null;
    }

    private function rememberSubmission(Request $request, Transaction $transaction): void
    {
        if ($token = $request->input('submit_token')) {
            DB::afterCommit(fn () => Cache::put('submit-token-done:' . $token, $transaction->id, now()->addDays(2)));
        }
    }

    /**
     * Check every line before anything is written, so a bad line stops the
     * whole sale with a message instead of failing halfway.
     */
    private function validateTransactionItems(Request $request): void
    {
        $errors = [];
        $items  = 0;

        foreach ([['barcodes', 'quantities', 'Barang'], ['barcodesretur_s', 'quantitiesretur_s', 'Barang retur']] as [$codes, $qtys, $label]) {
            foreach ((array) $request->input($codes, []) as $i => $good_unit_id) {
                if ($good_unit_id == null) {
                    continue;
                }

                $items++;
                $good_unit = GoodUnit::find($good_unit_id);
                $quantity  = $request->input($qtys)[$i] ?? null;

                if ($good_unit == null || $good_unit->good == null || $good_unit->unit == null) {
                    $errors[] = $label . ' baris ' . ($i + 1) . ': satuan barang tidak ditemukan (ID ' . $good_unit_id . '). Cek data satuan barang ini.';
                } elseif (! is_numeric($quantity) || $quantity <= 0) {
                    $errors[] = $label . ' ' . $good_unit->good->name . ': jumlah harus lebih dari 0.';
                }
            }
        }

        if ($items == 0) {
            $errors[] = 'Transaksi tidak berisi barang.';
        }

        if (! in_array($request->payment, ['cash', 'transfer'])) {
            $errors[] = 'Metode pembayaran tidak dikenal.';
        }

        if ($errors) {
            throw ValidationException::withMessages(['transaction' => $errors]);
        }
    }

    public function storeTransactionBase($role, $role_id, Request $request)
    {
        if ($previous = $this->previousSubmission($request)) {
            return $previous;
        }

        $this->validateTransactionItems($request);

        $hpp = 0;
        $sum = 0;

        if($request->member_name != null)
        {
            $data_member_new['name'] = $request->member_name;

            $member = Member::where('name', $request->member_name)->first();

            if($member == null)
                $member = Member::create($data_member_new);

            $data_transaction['member_id'] = $member->id;
        }
        else
        {
            $data_transaction['member_id'] = $request->member_id;
        }

        #tabel transaction
        if(count(array_filter((array) $request->barcodesretur_s, fn ($v) => $v != null)) > 0)
        {
            $data_transaction['type'] = 'retur'; 
        }
        else
        {
            $data_transaction['type'] = $request->type;
        }
        $data_transaction['role'] = $role;
        $data_transaction['role_id'] = $role_id;
        $data_transaction['total_item_price'] = unformatNumber($request->total_item_price);
        $data_transaction['total_discount_price'] = unformatNumber($request->total_discount_price);
        $data_transaction['total_sum_price'] = unformatNumber($request->total_sum_price);
        $data_transaction['voucher'] = $request->voucher;
        $data_transaction['voucher_nominal'] = unformatNumber($request->voucher_nominal);
        // $data_transaction['total_sum_price'] = unformatNumber($request->total_sum_price);
        $data_transaction['money_paid'] = unformatNumber($request->money_paid);
        $data_transaction['money_returned'] = unformatNumber($request->money_returned);
        $data_transaction['store']   = 'ntn getasan';
        $data_transaction['payment'] = $request->payment;
        $data_transaction['note']    = $request->note;
        $data_transaction['is_promo']= $request->is_promo;

        $transaction = Transaction::create($data_transaction);
        $this->rememberSubmission($request, $transaction);

        #tabel transaction detail
        $data_detail['transaction_id'] = $transaction->id;

        for ($i = 0; $i < sizeof((array) $request->barcodes); $i++)
        {
            if($request->barcodes[$i] != null)
            {
                $good_unit = GoodUnit::find($request->barcodes[$i]);
                $data_detail['good_unit_id']   = $good_unit->id;
                $data_detail['type']           = $request->type;
                $data_detail['quantity']       = $request->quantities[$i];
                $data_detail['real_quantity']  = $request->quantities[$i] * $good_unit->unit->quantity;
                $data_detail['last_stock']     = $good_unit->good->getStock();
                // HPP uses the buy price stored on the server, not the one the
                // cashier page loaded (it may be stale after a price change).
                $data_detail['buy_price']      = $good_unit->buy_price;
                $data_detail['selling_price']  = unformatNumber($request->prices[$i]);
                $data_detail['discount_price'] = unformatNumber($request->discounts[$i]);
                $data_detail['sum_price']      = unformatNumber($request->sums[$i]);

                TransactionDetail::create($data_detail);

                // Stock itself is recalculated from the details (StockService).
                $good_unit->good->update(['last_transaction' => date('Y-m-d')]);

                $sum += $data_detail['sum_price'];
                $hpp += $data_detail['buy_price'] * $data_detail['quantity'];
            }
        }

        $sum = $sum - checkNull($data_transaction['total_discount_price']) - checkNull($data_transaction['voucher_nominal']);

        #tabel journal transaksi
        if($request->payment == 'cash')
        {
            $data_journal['debit_account_id']   = Account::where('code', '1111')->first()->id;

            // lockForUpdate: two cashiers adding to the same daily journal at
            // the same moment must not overwrite each other's amount.
            $journal = Journal::where('journal_date', date('Y-m-d'))
                              ->where('type', 'transaction')
                              ->where('debit_account_id', $data_journal['debit_account_id'])
                              ->where('credit_account_id', Account::where('code', '4101')->first()->id)
                              ->whereNull('type_id')
                              ->lockForUpdate()
                              ->first();

            if($journal != null)
            {
                $data_journal['debit'] = floatval($journal->debit) + floatval($sum);
                $data_journal['credit'] = floatval($journal->credit) + floatval($sum);

                $journal->update($data_journal);
            }
            else
            {
                $data_journal['type']               = 'transaction';
                $data_journal['journal_date']       = date('Y-m-d');
                $data_journal['name']               = 'Penjualan tanggal ' . displayDate(date('Y-m-d'));
                $data_journal['debit']              = $sum;
                $data_journal['credit_account_id']  = Account::where('code', '4101')->first()->id;
                $data_journal['credit']             = $sum;

                Journal::create($data_journal);
            }
        }
        elseif($request->payment == 'transfer')
        {
            $data_journal['debit_account_id']   = Account::where('code', '1112')->first()->id;

            $data_journal['type']               = 'transaction';
            $data_journal['type_id']            = $transaction->id;
            $data_journal['journal_date']       = date('Y-m-d');
            $data_journal['name']               = 'Penjualan tanggal ' . displayDate(date('Y-m-d')) . ' ID ' . $transaction->id;
            $data_journal['debit']              = $sum;
            $data_journal['credit_account_id']  = Account::where('code', '4101')->first()->id;
            $data_journal['credit']             = $sum;

            Journal::create($data_journal);
        }


        #tabel journal hpp
        // Only the daily sales HPP journal (Debit HPP / Credit Persediaan).
        // Reverse and retur journals are also type 'hpp' but go the other
        // way; adding today's sales to one of them booked the whole day's
        // HPP as an increase of inventory (13 Jun 2024, 3 May 2025, 23 Jul 2025).
        $hpp_journal = Journal::where('journal_date', date('Y-m-d'))
                              ->where('type', 'hpp')
                              ->where('debit_account_id', Account::where('code', '5101')->first()->id)
                              ->where('credit_account_id', Account::where('code', '1141')->first()->id)
                              ->where('name', 'Penjualan tanggal ' . displayDate(date('Y-m-d')))
                              ->lockForUpdate()
                              ->first();

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
        if($data_transaction['money_paid'] < $data_transaction['total_sum_price'])
        {
            if($request->payment == 'cash')
            {
                $data_piutang['credit_account_id']   = Account::where('code', '1111')->first()->id;
            }
            elseif($request->payment == 'transfer')
            {
                $data_piutang['credit_account_id']   = Account::where('code', '1112')->first()->id;
            }
            $piutang = Journal::where('journal_date', date('Y-m-d'))
                              ->where('type', 'piutang')
                              ->where('name', 'like', 'Piutang dagang member ' . $transaction->member->name . ' (ID member ' . $transaction->member->id . ') %')
                              ->where('credit_account_id', $data_piutang['credit_account_id'])
                              ->first();

            if($piutang != null)
            {
                $data_piutang['name']   = $piutang->name . ', ' . $transaction->id;
                $data_piutang['debit']  = floatval($piutang->debit) + floatval($sum);
                $data_piutang['credit'] = floatval($piutang->credit) + floatval($sum);

                $piutang->update($data_piutang);
            }
            else
            {
                $data_piutang['type']               = 'piutang';
                $data_piutang['journal_date']       = date('Y-m-d');
                $data_piutang['name']               = 'Piutang dagang member ' . $transaction->member->name . ' (ID member ' . $transaction->member->id . ') -> ID transaksi ' . $transaction->id;
                $data_piutang['debit_account_id']   = Account::where('code', '1131')->first()->id;
                $data_piutang['debit']              = $sum;
                $data_piutang['credit']             = $sum;

                Journal::create($data_piutang);
            }

            if($data_transaction['money_paid'] > 0)
            {
                if($request->payment == 'cash')
                {
                    $data_piutang_member['debit_account_id']   = Account::where('code', '1111')->first()->id;
                }
                elseif($request->payment == 'transfer')
                {
                    $data_piutang_member['debit_account_id']   = Account::where('code', '1112')->first()->id;
                }
                $data_member['member_id']    = $transaction->member_id;
                $data_member['payment_date'] = date('Y-m-d');
                $data_member['money']        = $data_transaction['money_paid'];

                $payment = PiutangPayment::create($data_member);

                $piutang = Journal::where('journal_date', date('Y-m-d'))
                                  ->where('type', 'piutang_transaction')
                                  ->where('name', 'Pembayaran piutang member ' . $payment->member->name . ' (ID ' . $payment->member->id . ')')
                                  ->where('debit_account_id', $data_piutang_member['debit_account_id'])
                                  ->first();

                if($piutang != null)
                {
                    $data_piutang_member['debit'] = floatval($piutang->debit) + floatval($data_transaction['money_paid']);
                    $data_piutang_member['credit'] = floatval($piutang->credit) + floatval($data_transaction['money_paid']);

                    $piutang->update($data_piutang_member);
                }
                else
                {
                    $data_piutang_member['type']               = 'piutang_transaction';
                    $data_piutang_member['journal_date']       = date('Y-m-d');
                    $data_piutang_member['name']               = 'Pembayaran piutang member ' . $payment->member->name . ' (ID ' . $payment->member->id . ')';
                    $data_piutang_member['debit']              = $data_transaction['money_paid'];
                    $data_piutang_member['credit_account_id']  = Account::where('code', '1131')->first()->id;
                    $data_piutang_member['credit']             = $data_transaction['money_paid'];

                    Journal::create($data_piutang_member);
                }
            }
        }

        #tabel transaction detail retur
        $is_retur = false;
        $data_detail_retur['transaction_id'] = $transaction->id;
        $sum_retur = 0;
        $hpp_retur = 0;

        if(isset($request->barcodesretur_s))
        {
            for ($i = 0; $i < sizeof($request->barcodesretur_s); $i++) 
            { 
                if($request->barcodesretur_s[$i] != null)
                {
                    $good_unit_retur = GoodUnit::find($request->barcodesretur_s[$i]);
                    $good_retur      = $good_unit_retur->good;

                    $data_detail_retur['good_unit_id']   = $good_unit_retur->id;
                    $data_detail_retur['type']           = 'retur';
                    $data_detail_retur['quantity']       = $request->quantitiesretur_s[$i];
                    $data_detail_retur['real_quantity']  = $request->quantitiesretur_s[$i] * $good_unit_retur->unit->quantity;
                    $data_detail_retur['last_stock']     = $good_retur->getStock();
                    $data_detail_retur['buy_price']      = $good_unit_retur->buy_price;
                    $data_detail_retur['selling_price']  = unformatNumber($request->pricesretur_s[$i]);
                    $data_detail_retur['discount_price'] = unformatNumber($request->discountsretur_s[$i]);
                    $data_detail_retur['sum_price']      = unformatNumber($request->sumsretur_s[$i]);

                    // A 'retur' line is only a record of what came back; it
                    // does not change stock (getStock() skips it). The stock
                    // change is the loading below (good condition) or the
                    // retur box (damaged). Before, this line also lowered the
                    // stock of the LAST SOLD good, and crashed when the
                    // transaction had no sold goods at all.
                    TransactionDetail::create($data_detail_retur);

                    $sum_retur += unformatNumber($request->pricesretur_s[$i]) * $request->quantitiesretur_s[$i];
                    $hpp_retur += $data_detail_retur['buy_price'] * $data_detail_retur['quantity'];

                    $is_retur = true;

                    if($request->conditionsretur_s[$i] == 'rusak') #barang rusak
                    {
                        $last_buy = $good_retur->getLastBuy();
                        $distributor_id = $last_buy && $last_buy->good_loading && $last_buy->good_loading->distributor
                                        ? $last_buy->good_loading->distributor->id
                                        : $good_retur->last_distributor_id;

                        for($j = 0; $j < $data_detail_retur['quantity']; $j++)
                        {
                            $data_retur['good_id'] = $good_retur->id;
                            $data_retur['good_unit_id']   = $good_unit_retur->id;
                            $data_retur['last_distributor_id'] = $distributor_id;

                            ReturItem::create($data_retur);
                        }
                    }
                    else #barang gak rusak
                    {
                        $data_loading_retur = [
                            'role'             => $role,
                            'role_id'          => $role_id,
                            'checker'          => 'Load by sistem',
                            'loading_date'     => date('Y-m-d'),
                            'distributor_id'   => $good_retur->last_distributor_id,
                            'total_item_price' => $data_detail_retur['buy_price'] * $data_detail_retur['quantity'],
                            // the transaction ID lets a reverse find this loading
                            'note'             => 'Loading barang retur (ID transaksi ' . $transaction->id . ')',
                            'payment'          => $request->payment,
                        ];

                        $good_loading = GoodLoading::create($data_loading_retur);

                        GoodLoadingDetail::create([
                            'good_loading_id' => $good_loading->id,
                            'good_unit_id'    => $good_unit_retur->id,
                            'last_stock'      => $good_retur->getStock(),
                            'quantity'        => $data_detail_retur['quantity'],
                            'real_quantity'   => $data_detail_retur['real_quantity'],
                            'price'           => $data_detail_retur['buy_price'],
                            'selling_price'   => $data_detail_retur['selling_price'],
                            'expiry_date'     => null,
                        ]);

                        $good_retur->update(['last_loading' => date('Y-m-d')]);
                    }
                }
            }  
        }

        if($is_retur)
        {
            if($request->payment == 'cash')
            {
                $data_journal_loading_retur['credit_account_id']   = Account::where('code', '1111')->first()->id;
            }
            elseif($request->payment == 'transfer')
            {
                $data_journal_loading_retur['credit_account_id']   = Account::where('code', '1112')->first()->id;
            }

            // $sum_retur = $sum_retur - checkNull($data_transaction['total_discount_price']) - checkNull($data_transaction['voucher_nominal']);

            $data_journal_loading_retur['type']               = 'good_loading';
            $data_journal_loading_retur['journal_date']       = date('Y-m-d');
            $data_journal_loading_retur['name']               = 'Loading barang retur (ID transaksi ' . $transaction->id . ') tanggal ' . displayDate(date('Y-m-d'));
            $data_journal_loading_retur['debit_account_id']   = Account::where('code', '4101')->first()->id;
            $data_journal_loading_retur['debit']              = unformatNumber($sum_retur);
            if(count(array_filter((array) $request->barcodes, fn ($v) => $v != null)) == 0)
                $data_journal_loading_retur['credit_account_id']  = Account::where('code', '1111')->first()->id;
            $data_journal_loading_retur['credit']             = unformatNumber($sum_retur);

            Journal::create($data_journal_loading_retur);

            $data_journal_retur['type']               = 'hpp';
            $data_journal_retur['journal_date']       = date('Y-m-d');
            $data_journal_retur['name']               = 'HPP retur barang (ID transaksi ' . $transaction->id . ') tanggal ' . displayDate(date('Y-m-d'));
            $data_journal_retur['debit_account_id']   = Account::where('code', '1141')->first()->id;
            $data_journal_retur['debit']              = unformatNumber($hpp_retur);
            $data_journal_retur['credit_account_id']   = Account::where('code', '5101')->first()->id;
            $data_journal_retur['credit']             = unformatNumber($hpp_retur);

            Journal::create($data_journal_retur);
        }

        return $transaction;
    }

    /**
     * The loading that put a good-condition retur back on the shelf.
     * New ones carry the transaction ID in the note; older ones are matched
     * by time and good.
     */
    private function findReturLoading(Transaction $transaction, TransactionDetail $detail, ?Good $good): ?GoodLoading
    {
        $loading = GoodLoading::where('note', 'Loading barang retur (ID transaksi ' . $transaction->id . ')')
                              ->whereHas('details', fn ($q) => $q->where('good_unit_id', $detail->good_unit_id))
                              ->first();

        if ($loading == null && $good != null) {
            $loading = GoodLoading::where('note', 'Loading barang retur')
                                  ->where('checker', 'Load by sistem')
                                  ->whereBetween('created_at', [$transaction->created_at->copy()->subSeconds(10), $transaction->created_at->copy()->addSeconds(10)])
                                  ->whereHas('details', fn ($q) => $q->whereIn('good_unit_id', GoodUnit::withTrashed()->where('good_id', $good->id)->pluck('id')))
                                  ->first();
        }

        return $loading;
    }

    /**
     * Cancel a transaction ('not valid') or delete it ('deleted').
     *
     * Every effect of the transaction is undone and the journals are always
     * written, with amounts taken from the transaction itself. Before, the
     * journals depended on a guess ($is_retur, comparing the whole day's
     * sales with the day's journal) that was often wrong, e.g. when the retur
     * was worth more than the goods bought; then the stock came back but the
     * journal did not, or a retur stayed on the shelf after the cancel.
     */
    public function reverseTransactionBase($role, $role_id, $status, $transaction_id)
    {
        $transaction = Transaction::lockForUpdate()->find($transaction_id);

        // Already cancelled (double click / second tab): nothing to do again.
        if ($transaction == null || in_array($transaction->type, ['not valid', 'deleted'])) {
            return true;
        }

        $journal_status = $status == 'deleted' ? 'delete' : 'reverse';

        $hpp_sold    = 0; // buy value of sold goods that come back to stock
        $sales       = 0; // money of the sold goods (as journaled at sale)
        $hpp_retur   = 0; // buy value of returned goods that leave again
        $sales_retur = 0; // money that was paid back for the retur
        $good_loading = null;

        foreach ($transaction->details as $detail) {
            $good_unit = GoodUnit::withTrashed()->find($detail->good_unit_id);
            $good      = $good_unit ? Good::withTrashed()->find($good_unit->good_id) : null;

            if ($detail->type == 'retur') {
                $hpp_retur   += $detail->quantity * $detail->buy_price;
                $sales_retur += $detail->quantity * $detail->selling_price;

                $retur_loading = $this->findReturLoading($transaction, $detail, $good);

                if ($retur_loading) {
                    // good condition: it went back on the shelf, take it off again
                    $retur_loading->delete();
                } else {
                    // damaged: take it out of the retur box again
                    ReturItem::where('good_unit_id', $detail->good_unit_id)
                             ->whereNull('returned_date')
                             ->orderBy('id', 'desc')
                             ->limit((int) ceil($detail->quantity))
                             ->get()
                             ->each->delete();
                }

                // the line stays type 'retur' (never counted as stock)
                continue;
            }

            $sales    += $detail->sum_price;
            $hpp_sold += $detail->quantity * $detail->buy_price;

            $good_loading = GoodLoading::create([
                'role'             => $role,
                'role_id'          => $role_id,
                'checker'          => 'Created by system',
                'loading_date'     => date('Y-m-d'),
                'distributor_id'   => $good ? $good->last_distributor_id : null,
                'total_item_price' => $detail->quantity * $detail->buy_price,
                'note'             => 'Reverse transaction id ' . $transaction->id,
                'payment'          => 'cash',
            ]);

            GoodLoadingDetail::create([
                'good_loading_id' => $good_loading->id,
                'good_unit_id'    => $detail->good_unit_id,
                'last_stock'      => $good ? $good->getStock() : 0,
                'quantity'        => $detail->quantity,
                'real_quantity'   => $detail->real_quantity,
                'price'           => $detail->buy_price,
                'selling_price'   => $detail->selling_price,
            ]);

            $detail->update(['type' => $status]);
        }

        if (sizeof($transaction->details) > 0) {
            $cash_id       = Account::where('code', $transaction->payment == 'transfer' ? '1112' : '1111')->first()->id;
            $penjualan_id  = Account::where('code', '4101')->first()->id;
            $hpp_id        = Account::where('code', '5101')->first()->id;
            $persediaan_id = Account::where('code', '1141')->first()->id;
            $suffix        = ' transaction ID ' . $transaction->id . ($good_loading ? ' (loading ID ' . $good_loading->id . ')' : '');

            // The sale journal was: sold goods - discount - voucher. The retur
            // journal paid back the retur. Undo both in one entry; when the
            // retur was bigger the direction is turned around.
            $money = $sales > 0
                ? $sales - checkNull($transaction->total_discount_price) - checkNull($transaction->voucher_nominal)
                : 0;
            $money -= $sales_retur;

            if (abs($money) >= 0.01) {
                Journal::create([
                    'type'              => 'good_loading',
                    'type_id'           => $good_loading ? $good_loading->id : null,
                    'journal_date'      => date('Y-m-d'),
                    'name'              => 'Loading ' . $journal_status . $suffix,
                    'debit_account_id'  => $money > 0 ? $penjualan_id : $cash_id,
                    'debit'             => abs($money),
                    'credit_account_id' => $money > 0 ? $cash_id : $penjualan_id,
                    'credit'            => abs($money),
                ]);
            }

            // Inventory: sold goods come back (+), returned goods leave (-).
            $hpp = $hpp_sold - $hpp_retur;

            if (abs($hpp) >= 0.01) {
                Journal::create([
                    'type'              => 'hpp',
                    'journal_date'      => date('Y-m-d'),
                    'name'              => 'Penjualan ' . $journal_status . $suffix,
                    'debit_account_id'  => $hpp > 0 ? $persediaan_id : $hpp_id,
                    'debit'             => abs($hpp),
                    'credit_account_id' => $hpp > 0 ? $hpp_id : $persediaan_id,
                    'credit'            => abs($hpp),
                ]);
            }
        }

        $transaction->update(['type' => $status]);

        return true;
    }

    public function resumeTransactionBase($type, $category_id, $distributor_id, $start_date, $end_date, $pagination)
    {
        if($category_id == 'all')
            $whereCategory = '%%';
        else
            $whereCategory = $category_id;

        if($distributor_id == 'all')
            $whereDistributor = '%%';
        else
            $whereDistributor = $distributor_id;

        if($category_id == 'all' && $distributor_id == 'all')
        {
            $total = TransactionDetail::whereDate('transaction_details.created_at', '>=', $start_date)
                                      ->whereDate('transaction_details.created_at', '<=', $end_date) 
                                      ->where('transaction_details.type', $type)
                                      ->get();

            $transaction_details = TransactionDetail::join('good_units', 'good_units.id', 'transaction_details.good_unit_id')
                                                    ->join('goods', 'goods.id', 'good_units.good_id')
                                                    ->join('units', 'units.id', 'good_units.unit_id')
                                                    ->leftjoin('types', 'goods.type_id', 'types.id')
                                                    ->select(DB::raw("goods.id, goods.code, goods.name, goods.last_stock, units.name as unit_name, SUM(transaction_details.quantity) as quantity, transaction_details.buy_price, transaction_details.selling_price, types.name as type_name"))
                                                    ->whereDate('transaction_details.created_at', '>=', $start_date)
                                                    ->whereDate('transaction_details.created_at', '<=', $end_date) 
                                                    ->where('transaction_details.type', $type)
                                                    ->groupBy('goods.id')
                                                    ->groupBy('goods.code')
                                                    ->groupBy('goods.name')
                                                    ->groupBy('types.name')
                                                    ->groupBy('goods.last_stock')
                                                    ->groupBy('units.name')
                                                    ->groupBy('transaction_details.buy_price')
                                                    ->groupBy('transaction_details.selling_price')
                                                    ->orderBy('selling_price', 'desc')
                                                    ->orderBy('quantity', 'desc')
                                                    ->paginate($pagination);
        }
        else
        {
            $total = TransactionDetail::join('good_units', 'good_units.id', 'transaction_details.good_unit_id')
                                      ->join('goods', 'goods.id', 'good_units.good_id')
                                      ->where('transaction_details.type', $type)
                                      ->whereDate('transaction_details.created_at', '>=', $start_date)
                                      ->whereDate('transaction_details.created_at', '<=', $end_date) 
                                      ->whereRaw('goods.last_distributor_id like ? AND goods.category_id like ?', array($whereDistributor, $whereCategory))
                                      ->get();

            $transaction_details = TransactionDetail::join('good_units', 'good_units.id', 'transaction_details.good_unit_id')
                                                    ->join('goods', 'goods.id', 'good_units.good_id')
                                                    ->join('units', 'units.id', 'good_units.unit_id')
                                                    ->leftjoin('types', 'goods.type_id', 'types.id')
                                                    ->select(DB::raw("goods.id, goods.code, goods.name, goods.last_stock, units.name as unit_name, SUM(transaction_details.quantity) as quantity, transaction_details.buy_price, transaction_details.selling_price, types.name as type_name"))
                                                    ->where('transaction_details.type', $type)
                                                    ->whereDate('transaction_details.created_at', '>=', $start_date)
                                                    ->whereDate('transaction_details.created_at', '<=', $end_date) 
                                                    ->whereRaw('goods.last_distributor_id like ? AND goods.category_id like ?', array($whereDistributor, $whereCategory))
                                                    ->groupBy('goods.id')
                                                    ->groupBy('goods.code')
                                                    ->groupBy('goods.name')
                                                    ->groupBy('types.name')
                                                    ->groupBy('goods.last_stock')
                                                    ->groupBy('units.name')
                                                    ->groupBy('transaction_details.buy_price')
                                                    ->groupBy('transaction_details.selling_price')
                                                    ->orderBy('selling_price', 'desc')
                                                    ->orderBy('quantity', 'desc')
                                                    ->paginate($pagination);
        }

        return [$transaction_details, $total];
    }

    public function resumeTotalTransactionBase($start_date, $end_date)
    {
        $transactions['normal_cash'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                    ->whereDate('transactions.created_at', '<=', $end_date) 
                                                    ->where('type', 'normal')
                                                    ->where('payment', 'cash')
                                                    ->get();

        $transactions['normal_transfer'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                                    ->whereDate('transactions.created_at', '<=', $end_date) 
                                                    ->where('type', 'normal')
                                                    ->where('payment', 'transfer')
                                                    ->get();

        $transactions['retur_cash'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                            ->whereDate('transactions.created_at', '<=', $end_date) 
                                            ->where('type', 'retur')
                                            ->where('payment', 'cash')
                                            ->get();

        $transactions['retur_transfer'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                            ->whereDate('transactions.created_at', '<=', $end_date) 
                                            ->where('type', 'retur')
                                            ->where('payment', 'transfer')
                                            ->get();

        $transactions['not_valid'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                            ->whereDate('transactions.created_at', '<=', $end_date) 
                                            ->where('type', 'not valid')
                                            ->get();

        $transactions['credit'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                            ->whereDate('transactions.created_at', '<=', $end_date) 
                                            ->where('type', '2101')
                                            ->get();

        $transactions['piutang'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                            ->whereDate('transactions.created_at', '<=', $end_date) 
                                            ->where('type', '1131')
                                            ->get();

        $transactions['modal'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                            ->whereDate('transactions.created_at', '<=', $end_date) 
                                            ->where('type', '3001')
                                            ->get();

        $transactions['stock_opname'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                            ->whereDate('transactions.created_at', '<=', $end_date) 
                                            ->where('type', 'stock_opname')
                                            ->get();

        $transactions['internal'] = Transaction::whereDate('transactions.created_at', '>=', $start_date)
                                            ->whereDate('transactions.created_at', '<=', $end_date) 
                                            ->where('type', '!=', 'normal')
                                            ->where('type', '!=', 'retur')
                                            ->where('type', '!=', 'retur_item')
                                            ->where('type', '!=', 'stock_opname')
                                            ->where('type', '!=', 'not valid')
                                            ->where('type', '!=', 'deleted')
                                            ->where('type', '!=', '2101')
                                            ->where('type', '!=', '1131')
                                            ->where('type', '!=', '3001')
                                            ->get();

        $transactions['other_payment'] = Journal::join('accounts', 'accounts.id', 'journals.debit_account_id')
                                                ->whereRaw('(accounts.code = "5220" OR accounts.code = "5225")')
                                                ->where('journals.type', 'like', '%_payment%')
                                                ->whereDate('journals.journal_date', '>=', $start_date)
                                                ->whereDate('journals.journal_date', '<=', $end_date) 
                                                ->get();

        $transactions['other_transaction'] = Journal::where('type', 'like', '%_transaction')
                                                    ->whereDate('journals.journal_date', '>=', $start_date)
                                                    ->whereDate('journals.journal_date', '<=', $end_date) 
                                                    ->get();     

        $transactions['cash_account'] = Account::where('code', '1111')->first();
        $transactions['cash_in'] = Journal::where('debit_account_id', $transactions['cash_account']->id)->get();
        $transactions['cash_out'] = Journal::where('credit_account_id', $transactions['cash_account']->id)->get();

        return $transactions;                             
    }

    public function storeMoneyTransactionBase(Request $request)
    {
        $journal = Journal::where('name', 'Pengambilan uang tanggal ' . date('Y-m-d'))->where('debit', floatval(unformatNumber($request->money)))->first();

        if($journal != null)
        {
            return false;
        }

        $data_journal['type']               = 'cash_draw';
        $data_journal['journal_date']       = date('Y-m-d');
        $data_journal['name']               = 'Pengambilan uang tanggal ' . date('Y-m-d');
        $data_journal['debit_account_id']   = Account::where('code', '1113')->first()->id;
        $data_journal['debit']              = unformatNumber($request->money);
        $data_journal['credit_account_id']  = Account::where('code', '1111')->first()->id;
        $data_journal['credit']             = unformatNumber($request->money);

        Journal::create($data_journal);

        return true;
    }

    public function updateTransactionBase($role, $role_id, $transaction_id, Request $request)
    {
        $data = $request->input();
        $hpp = 0;
        $last_hpp = 0;
        $sum = 0;

        $transaction = Transaction::find($transaction_id);
        if($request->member_name != null)
        {
            $data_member_new['name'] = $request->member_name;

            $member = Member::where('name', $request->member_name)->first();

            if($member == null)
                $member = Member::create($data_member_new);

            $data_transaction['member_id'] = $member->id;
        }
        else
        {
            $data_transaction['member_id'] = $request->member_id;
        }

        #tabel transaction
        $data_transaction['total_item_price'] = unformatNumber($request->total_item_price);
        $data_transaction['total_discount_price'] = unformatNumber($request->total_discount_price);
        $data_transaction['total_sum_price'] = unformatNumber($request->total_sum_price);
        $data_transaction['money_paid'] = unformatNumber($request->money_paid);
        $data_transaction['money_returned'] = unformatNumber($request->money_returned);
        $data_transaction['store']   = config('app.name');
        $data_transaction['payment']    = $transaction->payment;
        $data_transaction['note']    = $request->note;
        
        $last_sum = $transaction->total_sum_price;
        $transaction->update($data_transaction);

        for($i = 0; $i < sizeof($transaction->details); $i++) 
        { 
            if($request->ids[$i] != null)
            {
                $transaction_detail = TransactionDetail::find($request->ids[$i]);

                $good_unit = $transaction_detail->good_unit;

                if($good_unit)
                {
                    $data_detail['quantity']       = $request->quantities[$i];
                    $data_detail['real_quantity']  = $request->quantities[$i] * $good_unit->unit->quantity;
                    $data_detail['sum_price']      = unformatNumber($request->sum_prices[$i]);

                    $sum += $data_detail['sum_price'];
                    $last_hpp += $transaction_detail->buy_price * $transaction_detail->quantity;
                    $hpp += $transaction_detail->buy_price * $data_detail['quantity'];

                    $good = $transaction_detail->good_unit->good;

                    $data_good['total_loading']     = $good->total_loading;
                    $data_good['total_transaction'] = $good->total_transaction - round($transaction_detail->real_quantity / $good->base_unit()->unit->quantity, 3) + round($data_detail['real_quantity'] / $good->base_unit()->unit->quantity, 3);
                    $data_good['last_stock']        = $good->total_loading - $data_good['total_transaction'];
                    $data_good['last_transaction']  = date('Y-m-d');
                    $good->update($data_good);

                    $transaction_detail->update($data_detail);
                }
            }
        }

        if($transaction->payment == 'cash')
        {
            $account = Account::where('code', '1111')->first()->id;
        }
        else
        {
            $account = Account::where('code', '1112')->first()->id;
        }
        // dd($transaction);die;

        $journal = Journal::where('type_id', $transaction->id)->first();

        if($journal == null)
        {
            $journal = Journal::where('name', 'Penjualan tanggal ' . displayDate(date('Y-m-d', strtotime($transaction->created_at))))
                              ->where('type', 'transaction')
                              ->where('debit_account_id', $account)
                              ->first();

            $hpp = Journal::where('name', 'Penjualan tanggal ' . displayDate(date('Y-m-d', strtotime($transaction->created_at))))
                          ->where('type', 'hpp')
                          ->first();

            $data_hpp['debit']              = $hpp->debit - $last_sum + $sum;
            $data_hpp['credit']             = $hpp->credit - $last_sum + $sum;

            $hpp->update($data_hpp);
        }

        $data_journal['debit']  = $journal->debit - $last_sum + $sum;
        $data_journal['credit'] = $journal->credit - $last_sum + $sum;
        $data_journal['name']   = $journal->name . ' add on ' . $transaction->id;

        $journal->update($data_journal);

       return $transaction; 
    }
}