<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;
use App\Imports\LoadingImport;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\Account;
use App\Models\Distributor;
use App\Models\Good;
use App\Models\GoodLoading;
use App\Models\GoodLoadingDetail;
use App\Models\GoodPrice;
use App\Models\GoodUnit;
use App\Models\Journal;
use App\Models\Transaction;
use App\Models\TransactionDetail;

trait GoodLoadingControllerBase 
{
    public function indexGoodLoadingBase($start_date, $end_date, $distributor_id, $pagination)
    {
        if($distributor_id == "all")
        {
            if($pagination == 'all')
            $good_loadings = GoodLoading::whereDate('good_loadings.created_at', '>=', $start_date)
                                        ->whereDate('good_loadings.created_at', '<=', $end_date) 
                                        ->orderBy('good_loadings.loading_date','asc')
                                        ->get();
            else
            $good_loadings = GoodLoading::whereDate('good_loadings.created_at', '>=', $start_date)
                                        ->whereDate('good_loadings.created_at', '<=', $end_date)
                                        ->orderBy('good_loadings.loading_date','asc')
                                        ->paginate($pagination);
        }
        else
        {
            if($pagination == 'all')
            $good_loadings = GoodLoading::whereDate('good_loadings.created_at', '>=', $start_date)
                                        ->whereDate('good_loadings.created_at', '<=', $end_date)
                                        ->where('good_loadings.distributor_id', $distributor_id)
                                        ->orderBy('good_loadings.loading_date','asc')
                                        ->get();
            else
            $good_loadings = GoodLoading::whereDate('good_loadings.created_at', '>=', $start_date)
                                        ->whereDate('good_loadings.created_at', '<=', $end_date)
                                        ->where('good_loadings.distributor_id', $distributor_id)
                                        ->orderBy('good_loadings.loading_date','asc')
                                        ->paginate($pagination);
        }

        return $good_loadings;
    }

    public function storeGoodLoadingBase($role, $role_id, $type, Request $request)
    {
        $data = $request->input();

        // if($type == 'internal')
        //     $data['distributor_name'] = 'Loading internal';

        if($data['distributor_name'] != null)
        {
            $distributor = Distributor::where('name', $data['distributor_name'])->first();

            if($distributor == null)
            {
                $data_distributor['name'] = $data['distributor_name'];

                $distributor = Distributor::create($data_distributor);
            }
            $data['distributor_id'] = $distributor->id;
        }

        $request->total_item_price = unformatNumber($request->total_item_price);
        $data_loading['note']      = $data['note'];
        $data_loading['total_item_price'] = $request->total_item_price;
        if($data['payment'] != '1112')
        {
            if($request->total_item_price % 1000 > 0 && $request->total_item_price % 1000 <= 500 && $request->total_item_price > 1000)
            {
                $data_loading['total_item_price'] = intval($request->total_item_price / 1000) * 1000 + 500;
                $data_loading['note'] .= " (pembulatan dari " . $request->total_item_price . ")";
            }
            elseif($request->total_item_price % 1000 > 500 && $request->total_item_price > 1000)
            {
                $data_loading['total_item_price'] = intval($request->total_item_price / 1000) * 1000 + 1000;
                $data_loading['note'] .= " (pembulatan dari " . $request->total_item_price . ")";
            }
        }

        $data_loading['role']         = $role;
        $data_loading['role_id']      = $role_id;
        $data_loading['checker']      = $data['checker'];
        $data_loading['loading_date'] = $data['loading_date'];
        $data_loading['distributor_id']   = $data['distributor_id'];
        // $data_loading['total_item_price'] = unformatNumber($request->total_item_price);
        $data_loading['payment']      = $data['payment'];
        $data_loading['type']         = $type;

        $last_loading = GoodLoading::orderBy('id', 'desc')->first();

        if($last_loading->distributor_id == $data_loading['distributor_id'] && $last_loading->loading_date == $data_loading['loading_date'] && $last_loading->total_item_price == $data_loading['total_item_price'] && $last_loading->note == $data_loading['note'])
        {
            return $last_loading;
        }
        else
        {
            if($type == 'transaction-internal')
            {
                #tabel transaction
                $data_transaction['type'] = '1131';
                $data_transaction['role'] = $role;
                $data_transaction['role_id'] = $role_id;
                $data_transaction['member_id'] = 1;
                $data_transaction['total_item_price'] = unformatNumber($data_loading['total_item_price']);
                $data_transaction['total_discount_price'] = 0;
                $data_transaction['total_sum_price'] = unformatNumber($data_loading['total_item_price']);
                $data_transaction['money_paid'] = unformatNumber($data_loading['total_item_price']);
                $data_transaction['money_returned'] = 0;
                $data_transaction['store']   = 'offline';
                $data_transaction['payment'] = $data_loading['payment'];
                $data_transaction['note']    = $data_loading['note'] ;

                $transaction = Transaction::create($data_transaction);
                $data_detail['transaction_id'] = $transaction->id;
                $hpp = 0;
            }

            $good_loading = GoodLoading::create($data_loading);
            $laba_goods = [];

            for($i = 0; $i < sizeof($data['names']); $i++) 
            { 
                if($data['names'][$i] != null)
                {

                    $data_good['code'] = $data['barcodes'][$i];
                    $data_good['name'] = $data['name_temps'][$i];

                    $good = Good::find($data['names'][$i]);

                    if($type != 'internal')
                    {
                        $data_good['last_distributor_id'] = $data['distributor_id'];
                    }

                    $good->update($data_good);

                    $good_unit = GoodUnit::where('good_id', $data['names'][$i])
                                         ->where('unit_id', $data['units'][$i])
                                         ->first();

                    if($good_unit)
                    {
                        if($good_unit->selling_price != $data['sell_prices'][$i])
                        {
                            $data_price['role']         = $role;
                            $data_price['role_id']      = $role_id;
                            $data_price['good_unit_id'] = $good_unit->id;
                            $data_price['old_price']    = $good_unit->selling_price;
                            $data_price['recent_price'] = $data['sell_prices'][$i];
                            $data_price['reason']       = 'Diubah saat loading';

                            GoodPrice::create($data_price);
                        }

                        #journal penambahan barang kalau harga beli naik
                        if((floatval($good_unit->buy_price) < floatval($data['prices'][$i])) && $good_unit->good->getStockWoLastLoad($good_loading->id) > 0 && !in_array($good_unit->good->id, $laba_goods))
                        {
                            $account_buy = Account::where('code', '1141')->first();

                            // $payment_buy = Journal::whereDate('journal_date', date('Y-m-d'))->where('debit_account_id', $account_buy->id)->first();

                            $amount = $good_unit->good->getStockWoLastLoad($good_loading->id) * (($data['prices'][$i] - $good_unit->buy_price) / $good_unit->unit->quantity);

                            // if($payment_buy != null)
                            // {
                            //     $data_payment_buy['debit'] = floatval($payment_buy->debit) + floatval($amount);
                            //     $data_payment_buy['credit'] = floatval($payment_buy->credit) + floatval($amount);

                            //     $payment_buy->update($data_payment_buy);
                            // }
                            // else
                            // {
                                $data_payment_buy['type']               = 'other_payment';
                                $data_payment_buy['journal_date']       = date('Y-m-d');
                                $data_payment_buy['name']               = 'Laba kenaikan harga barang ' . $good_unit->good->name . ' id good unit ' . $good_unit->id . ' (Loading barang ' . $good_loading->distributor->name . ' tanggal ' . displayDate($good_loading->loading_date) . ')';
                                $data_payment_buy['debit_account_id']   = $account_buy->id;
                                $data_payment_buy['debit']              = $amount;
                                $data_payment_buy['credit_account_id']  = Account::where('code', '5215')->first()->id;
                                $data_payment_buy['credit']             = $amount;

                                Journal::create($data_payment_buy);
                            // }

                            array_push($laba_goods, $good_unit->good->id);
                        }
                        elseif((floatval($good_unit->buy_price) > floatval($data['prices'][$i])) && $good_unit->good->getStockWoLastLoad($good_loading->id) > 0 && !in_array($good_unit->good->id, $laba_goods)) #journal penyusutan kalau harga beli turun
                        {
                            $account_buy = Account::where('code', '5215')->first();

                            // $payment_buy = Journal::whereDate('journal_date', date('Y-m-d'))->where('debit_account_id', $account_buy->id)->first();

                            $amount = $good_unit->good->getStockWoLastLoad($good_loading->id) * (($good_unit->buy_price - $data['prices'][$i]) / $good_unit->unit->quantity);

                            // if($payment_buy != null)
                            // {
                            //     $data_payment_buy['debit'] = floatval($payment_buy->debit) + floatval($amount);
                            //     $data_payment_buy['credit'] = floatval($payment_buy->credit) + floatval($amount);

                            //     $payment_buy->update($data_payment_buy);
                            // }
                            // else
                            // {
                                $data_payment_buy['type']               = 'other_payment';
                                $data_payment_buy['journal_date']       = date('Y-m-d');
                                $data_payment_buy['name']               = $account_buy->name . ' (penyusutan harga barang ' . $good_unit->good->name . ' id good unit ' . $good_unit->id . ' dari loading barang ' . $good_loading->distributor->name . ' tanggal ' . displayDate($good_loading->loading_date) . ')';
                                $data_payment_buy['debit_account_id']   = $account_buy->id;
                                $data_payment_buy['debit']              = $amount;
                                $data_payment_buy['credit_account_id']  = Account::where('code', '1141')->first()->id;
                                $data_payment_buy['credit']             = $amount;

                                Journal::create($data_payment_buy);
                            // }

                            array_push($laba_goods, $good_unit->good->id);
                        }

                        $data_unit['good_id']       = $data['names'][$i];
                        $data_unit['unit_id']       = $data['units'][$i];
                        $data_unit['buy_price']     = $data['prices'][$i];
                        $data_unit['selling_price'] = $data['sell_prices'][$i];

                        $good_unit->update($data_unit);
                    }
                    else
                    {
                        $data_unit['good_id']       = $data['names'][$i];
                        $data_unit['unit_id']       = $data['units'][$i];
                        $data_unit['buy_price']     = $data['prices'][$i];
                        $data_unit['selling_price'] = $data['sell_prices'][$i];

                        $good_unit = GoodUnit::create($data_unit);

                        $data_price['role']         = $role;
                        $data_price['role_id']      = $role_id;
                        $data_price['good_unit_id'] = $good_unit->id;
                        $data_price['old_price']    = $good_unit->selling_price;
                        $data_price['recent_price'] = $data['sell_prices'][$i];
                        $data_price['reason']       = 'Harga pertama';

                        GoodPrice::create($data_price);
                    }

                    $data_detail['good_loading_id'] = $good_loading->id;
                    $data_detail['good_unit_id']    = $good_unit->id;
                    $data_detail['last_stock']      = $data['old_stocks'][$i];
                    $data_detail['quantity']        = $data['quantities'][$i];
                    $data_detail['real_quantity']   = $data['quantities'][$i] * $good_unit->unit->quantity;
                    $data_detail['price']           = $data['prices'][$i];
                    $data_detail['selling_price']   = $data['sell_prices'][$i];
                    $data_detail['expiry_date']     = $data['exp_dates'][$i];

                    GoodLoadingDetail::create($data_detail);

                    if($type == 'transaction-internal')
                    {
                        $data_detail['good_unit_id']   = $good_unit->id;
                        $data_detail['type']           = $request->type;
                        $data_detail['quantity']       = $data_detail['quantity'] ;
                        $data_detail['real_quantity']  = $data_detail['real_quantity'] ;
                        $data_detail['buy_price']      = $data_detail['price'] ;
                        $data_detail['selling_price']  = $data_detail['selling_price'] ;
                        $data_detail['discount_price'] = 0;
                        $data_detail['sum_price']      = $data_detail['quantity'] * $data_detail['buy_price'];

                        TransactionDetail::create($data_detail);

                        $hpp += $data_detail['sum_price'];
                    }
                }
            }

            #tabel journal 
            $account = Account::where('code', $data['payment'])->first();

            $data_journal['type']               = 'good_loading';
            $data_journal['type_id']            = $good_loading->id;
            $data_journal['journal_date']       = $data['loading_date'];
            $data_journal['name']               = 'Loading barang ' . $good_loading->distributor->name . ' tanggal ' . displayDate($good_loading->loading_date) . '(ID loading ' . $good_loading->id . ')';
            $data_journal['debit_account_id']   = Account::where('code', '1141')->first()->id;
            $data_journal['debit']              = unformatNumber($good_loading->total_item_price);
            $data_journal['credit_account_id']  = $account->id;
            $data_journal['credit']             = unformatNumber($good_loading->total_item_price);

            Journal::create($data_journal); 

            if($type == 'transaction-internal')
            {
               #journal penyusutan barang
                if($request->type == '5215')
                {
                    $data_pb['type']               = 'penyusutan';
                    $data_pb['type_id']            = $transaction->id;
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
                    $data_op['type_id']            = $transaction->id;
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
                    $data_ud['type_id']            = $transaction->id;
                    $data_ud['journal_date']       = date('Y-m-d');
                    $data_ud['name']               = 'Hutang dagang distributor ' . $distributor->name . ' (ID transaksi ' . $transaction->id . ')';
                    $data_ud['debit_account_id']   = Account::where('code', '2101')->first()->id;
                    $data_ud['debit']              = $data_transaction['total_sum_price'];
                    $data_ud['credit_account_id']  = Account::where('code', '1141')->first()->id;
                    $data_ud['credit']             = $data_transaction['total_sum_price'];

                    Journal::create($data_ud);
                }

                #journal piutang dagang
                if($request->type == '1131')
                {
                    $distributor = Distributor::find($request->distributor_id);

                    $data_ud['type']               = 'piutang dagang ' . $distributor->id;
                    $data_ud['type_id']            = $transaction->id;
                    $data_ud['journal_date']       = date('Y-m-d');
                    $data_ud['name']               = 'Piutang dagang distributor ' . $distributor->name . ' (ID transaksi ' . $transaction->id . ')';
                    $data_ud['debit_account_id']   = Account::where('code', '1131')->first()->id;
                    $data_ud['debit']              = $data_transaction['total_sum_price'];
                    $data_ud['credit_account_id']  = Account::where('code', '1141')->first()->id;
                    $data_ud['credit']             = $data_transaction['total_sum_price'];

                    Journal::create($data_ud);
                }

                #journal modal pemilik
                if($request->type == '3001')
                {
                    $data_ud['type']               = 'modal pemilik';
                    $data_ud['type_id']            = $transaction->id;
                    $data_ud['journal_date']       = date('Y-m-d');
                    $data_ud['name']               = 'Modal pemilik transaksi internal (ID transaksi ' . $transaction->id . ')';
                    $data_ud['debit_account_id']   = Account::where('code', '3001')->first()->id;
                    $data_ud['debit']              = $data_transaction['total_sum_price'];
                    $data_ud['credit_account_id']  = Account::where('code', '1141')->first()->id;
                    $data_ud['credit']             = $data_transaction['total_sum_price'];

                    Journal::create($data_ud);
                } 
            }
            
            return $good_loading;
        }
    }

    public function storeExcelGoodLoadingBase($role, $role_id, Request $request)
    {
        // $data = $request
        if($request->hasFile('file')) 
        {
            $distributor = Distributor::where('name', $request->distributor_name)->first();

            if($distributor == null)
            {
                if($request->distributor_id == null || $request->distributor_id == 'null')
                {
                    $data_distributor['name'] = $request->distributor_name;

                    $distributor = Distributor::create($data_distributor);
                }
                else
                {
                    $distributor = Distributor::find($request->distributor_id);
                }
            }

            $data_loading['role']             = $role;
            $data_loading['role_id']          = $role_id;
            $data_loading['checker']          = "Upload by sistem";
            $data_loading['loading_date']     = date('Y-m-d');
            $data_loading['distributor_id']   = $distributor->id;
            $data_loading['total_item_price'] = $request->total_item_price;
            $data_loading['note']             = "Upload by sistem";
            $data_loading['payment']          = "cash";

            $good_loading = GoodLoading::create($data_loading);
            Excel::import(new LoadingImport($role, $role_id, $good_loading->id), $request->file('file'));

            #tabel journal 
            $data_journal['type']               = 'good_loading';
            $data_journal['type_id']            = $good_loading->id;
            $data_journal['journal_date']       = date('Y-m-d');
            $data_journal['name']               = 'Loading barang ' . $good_loading->distributor->name . ' tanggal ' . displayDate($good_loading->loading_date) . ' (ID loading ' . $good_loading->id . ')';
            $data_journal['debit_account_id']   = Account::where('code', '1141')->first()->id;
            $data_journal['debit']              = unformatNumber($request->total_item_price);
            $data_journal['credit_account_id']  = Account::where('code', '3001')->first()->id; #modal awal pas pertama upload barang
            $data_journal['credit']             = unformatNumber($request->total_item_price);

            Journal::create($data_journal);

            return $good_loading;
        }
    }

    public function updateGoodLoadingBase($role, $role_id, $good_loading_id, Request $request)
    {
        // dd($request);die;
        $data = $request->input();

        $request->total_item_price = unformatNumber($request->total_item_price);
        $data_loading['note']      = $data['note'];
        $data_loading['total_item_price'] = $request->total_item_price;
        if($data['payment'] != '1112')
        {
            if($request->total_item_price % 1000 > 0 && $request->total_item_price % 1000 <= 500 && $request->total_item_price > 1000)
            {
                $data_loading['total_item_price'] = intval($request->total_item_price / 1000) * 1000 + 500;
                $data_loading['note'] .= " (pembulatan dari " . $request->total_item_price . ")";
            }
            elseif($request->total_item_price % 1000 > 500 && $request->total_item_price > 1000)
            {
                $data_loading['total_item_price'] = intval($request->total_item_price / 1000) * 1000 + 1000;
                $data_loading['note'] .= " (pembulatan dari " . $request->total_item_price . ")";
            }
        }

        $data_loading['checker']      = $data['checker'];
        $data_loading['loading_date'] = $data['loading_date'];
        $data_loading['distributor_id'] = $data['distributor_id'];
        $data_loading['payment']      = $data['payment'];

        $good_loading = GoodLoading::find($good_loading_id);

        if($good_loading->distributor_id != $data['distributor_id'])
        {
            foreach($good_loading->details as $detail)
            {
                $data_detail['last_distributor_id'] = $data['distributor_id'];

                $good = Good::find($detail->good_unit->good_id);
                $good->update($data_detail);
            }
        }
        $good_loading->update($data_loading);
        $laba_goods = [];

        $change_ids = explode(';', $data['change']);

        $data_good['last_distributor_id'] = $data['distributor_id'];

        for($i = 0; $i < sizeof($change_ids); $i++) 
        { 
            if($change_ids[$i] != null)
            {
                $j = $change_ids[$i] - 1;
                $good_loading_detail = GoodLoadingDetail::find($data['ids'][$j]);

                $good_unit = $good_loading_detail->good_unit;

                $good = Good::find($good_unit->good_id);
                // if($good->code != $data['barcodes'][$i])
                //     $data_good['code'] = $data['barcodes'][$i];
                // else
                $data_good['name'] = $data['name_temps'][$j];

                $good->update($data_good);

                if($good_unit)
                {
                    if($good_unit->selling_price != $data['sell_prices'][$j])
                    {
                        $data_price['role']         = $role;
                        $data_price['role_id']      = $role_id;
                        $data_price['good_unit_id'] = $good_unit->id;
                        $data_price['old_price']    = $good_unit->selling_price;
                        $data_price['recent_price'] = $data['sell_prices'][$j];
                        $data_price['reason']       = 'Diubah saat loading';

                        GoodPrice::create($data_price);
                    }

                    #journal penambahan barang kalau harga beli naik
                    if((floatval($good_unit->buy_price) < floatval($data['prices'][$j])) && $good_unit->good->getStockWoLastLoad($good_loading->id) > 0 && !in_array($good_unit->good->id, $laba_goods))
                    {
                        $account_buy = Account::where('code', '1141')->first();

                        $amount = $good_unit->good->getStockWoLastLoad($good_loading->id) * (($data['prices'][$j] - $good_unit->buy_price) / $good_unit->unit->quantity);

                        $data_payment_buy['type']               = 'other_payment';
                        $data_payment_buy['journal_date']       = date('Y-m-d');
                        $data_payment_buy['name']               = 'Laba kenaikan harga barang ' . $good_unit->good->name . ' id good unit ' . $good_unit->id . ' (Loading barang ' . $good_loading->distributor->name . ' tanggal ' . displayDate($good_loading->loading_date) . ')';
                        $data_payment_buy['debit_account_id']   = $account_buy->id;
                        $data_payment_buy['debit']              = $amount;
                        $data_payment_buy['credit_account_id']  = Account::where('code', '5215')->first()->id;
                        $data_payment_buy['credit']             = $amount;

                        Journal::create($data_payment_buy);

                        array_push($laba_goods, $good_unit->good->id);
                    }
                    elseif((floatval($good_unit->buy_price) > floatval($data['prices'][$j])) && $good_unit->good->getStockWoLastLoad($good_loading->id) > 0 && !in_array($good_unit->good->id, $laba_goods)) #journal penyusutan kalau harga beli turun
                    {
                        $account_buy = Account::where('code', '5215')->first();

                        $amount = $good_unit->good->getStockWoLastLoad($good_loading->id) * (($good_unit->buy_price - $data['prices'][$j]) / $good_unit->unit->quantity);

                        $data_payment_buy['type']               = 'other_payment';
                        $data_payment_buy['journal_date']       = date('Y-m-d');
                        $data_payment_buy['name']               = $account_buy->name . ' (penyusutan harga barang ' . $good_unit->good->name . ' id good unit ' . $good_unit->id . ' dari loading barang ' . $good_loading->distributor->name . ' tanggal ' . displayDate($good_loading->loading_date) . ')';
                        $data_payment_buy['debit_account_id']   = $account_buy->id;
                        $data_payment_buy['debit']              = $amount;
                        $data_payment_buy['credit_account_id']  = Account::where('code', '1141')->first()->id;
                        $data_payment_buy['credit']             = $amount;

                        Journal::create($data_payment_buy);

                        array_push($laba_goods, $good_unit->good->id);
                    }

                    $data_unit['buy_price']     = $data['prices'][$j];
                    $data_unit['selling_price'] = $data['sell_prices'][$j];

                    $good_unit->update($data_unit);
                }

                $data_detail['quantity']        = $data['quantities'][$j];
                $data_detail['real_quantity']   = $data['quantities'][$j] * $good_unit->unit->quantity;
                $data_detail['price']           = $data['prices'][$j];
                $data_detail['selling_price']   = $data['sell_prices'][$j];

                $good_loading_detail->update($data_detail);
            }
        }

        $journal = Journal::where('type', 'good_loading')->where('type_id', $good_loading->id)->first();

        $data_journal['journal_date']       = $data['loading_date'];
        $data_journal['name']               = 'Edit loading barang ' . $good_loading->distributor->name . ' tanggal ' . displayDate($good_loading->loading_date) . '(ID loading ' . $good_loading->id . ')';
        $data_journal['debit']              = unformatNumber($good_loading->total_item_price);
        $data_journal['credit']             = unformatNumber($good_loading->total_item_price);
        $data_journal['credit_account_id']  = Account::where('code', $request->payment)->first()->id;

       $journal->update($data_journal);

       return $good_loading; 
    }

    public function deleteGoodLoadingBase($good_loading_id)
    {
        $good_loading = GoodLoading::find($good_loading_id);

        $journal = Journal::where('type','good_loading')
                          ->where('type_id', $good_loading->id)
                          ->first();

        $journal->delete();
        $good_loading->delete();

        return true;
    }
}
