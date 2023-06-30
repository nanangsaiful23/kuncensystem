<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;
use App\Imports\LoadingImport;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\Account;
use App\Models\Distributor;
use App\Models\GoodLoading;
use App\Models\GoodLoadingDetail;
use App\Models\GoodPrice;
use App\Models\GoodUnit;
use App\Models\Journal;

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

    public function storeGoodLoadingBase($role, $role_id, Request $request)
    {
        $data = $request->input();

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

        $data_loading['role']         = $role;
        $data_loading['role_id']      = $role_id;
        $data_loading['checker']      = $data['checker'];
        $data_loading['loading_date'] = $data['loading_date'];
        $data_loading['distributor_id']   = $data['distributor_id'];
        $data_loading['total_item_price'] = unformatNumber($request->total_item_price);
        $data_loading['note']             = $data['note'];
        $data_loading['payment']          = $data['payment'];

        $good_loading = GoodLoading::create($data_loading);

        for($i = 0; $i < sizeof($data['names']); $i++) 
        { 
            if($data['names'][$i] != null)
            {
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
                    if($good_unit->buy_price < $data['prices'][$i])
                    {
                        $account_buy = Account::where('code', '1141')->first();

                        $payment_buy = Journal::whereDate('journal_date', date('Y-m-d'))->where('debit_account_id', $account_buy->id)->first();

                        $amount = $good_unit->good->getStock() * ($data['prices'][$i] - $good_unit->buy_price);

                        if($payment_buy != null)
                        {
                            $data_payment_buy['debit'] = floatval($payment_buy->debit) + floatval($amount);
                            $data_payment_buy['credit'] = floatval($payment_buy->credit) + floatval($amount);

                            $payment_buy->update($data_payment_buy);
                        }
                        else
                        {
                            $data_payment_buy['type']               = 'other_payment';
                            $data_payment_buy['journal_date']       = date('Y-m-d');
                            $data_payment_buy['name']               = 'Laba kenaikan harga barang (Loading barang ' . $good_loading->distributor->name . ' tanggal ' . displayDate($good_loading->loading_date) . ')';
                            $data_payment_buy['debit_account_id']   = $account_buy->id;
                            $data_payment_buy['debit']              = $amount;
                            $data_payment_buy['credit_account_id']  = Account::where('code', '5215')->first()->id;
                            $data_payment_buy['credit']             = $amount;

                            Journal::create($data_payment_buy);
                        }
                    }
                    elseif($good_unit->buy_price > $data['prices'][$i]) #journal penyusutan kalau harga beli turun
                    {
                        $account_buy = Account::where('code', '5215')->first();

                        $payment_buy = Journal::whereDate('journal_date', date('Y-m-d'))->where('debit_account_id', $account_buy->id)->first();

                        $amount = $good_unit->good->getStock() * ($good_unit->buy_price - $data['prices'][$i]);

                        if($payment_buy != null)
                        {
                            $data_payment_buy['debit'] = floatval($payment_buy->debit) + floatval($amount);
                            $data_payment_buy['credit'] = floatval($payment_buy->credit) + floatval($amount);

                            $payment_buy->update($data_payment_buy);
                        }
                        else
                        {
                            $data_payment_buy['type']               = 'other_payment';
                            $data_payment_buy['journal_date']       = date('Y-m-d');
                            $data_payment_buy['name']               = $account_buy->name . ' (Loading barang ' . $good_loading->distributor->name . ' tanggal ' . displayDate($good_loading->loading_date) . ')';
                            $data_payment_buy['debit_account_id']   = $account_buy->id;
                            $data_payment_buy['debit']              = $amount;
                            $data_payment_buy['credit_account_id']  = Account::where('code', '1141')->first()->id;
                            $data_payment_buy['credit']             = $amount;

                            Journal::create($data_payment_buy);
                        }
                    }

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
                $data_detail['last_stock']      = $data['stocks'][$i];
                $data_detail['quantity']        = $data['quantities'][$i];
                $data_detail['real_quantity']   = $data['quantities'][$i] * $good_unit->unit->quantity;
                $data_detail['price']           = $data['prices'][$i];
                $data_detail['selling_price']   = $data['sell_prices'][$i];
                $data_detail['expiry_date']     = $data['exp_dates'][$i];

                GoodLoadingDetail::create($data_detail);
            }
        }

        #tabel journal 
        $account = Account::where('code', $data['payment'])->first();
        // $journal = Journal::whereDate('journal_date', $data['loading_date'])->where('type', 'good_loading')->where('credit_account_id', $account->id)->first();

        // if($journal != null)
        // {
        //     $data_journal['debit'] = floatval($journal->debit) + floatval(unformatNumber($request->total_item_price));
        //     $data_journal['credit'] = floatval($journal->credit) + floatval(unformatNumber($request->total_item_price));

        //     $journal->update($data_journal);
        // }
        // else
        // {
            $data_journal['type']               = 'good_loading';
            $data_journal['journal_date']       = $data['loading_date'];
            $data_journal['name']               = 'Loading barang ' . $good_loading->distributor->name . ' tanggal ' . displayDate($good_loading->loading_date);
            $data_journal['debit_account_id']   = Account::where('code', '1141')->first()->id;
            $data_journal['debit']              = unformatNumber($request->total_item_price);
            $data_journal['credit_account_id']  = $account->id;
            $data_journal['credit']             = unformatNumber($request->total_item_price);

            Journal::create($data_journal);
        // }

        return $good_loading;
    }

    public function storeExcelGoodLoadingBase($role, $role_id, Request $request)
    {
        // $data = $request
        if($request->hasFile('file')) 
        {
            $distributor = Distributor::where('name', $request->distributor_name)->first();

            if($distributor == null)
            {
                $data_distributor['name'] = $request->distributor_name;

                $distributor = Distributor::create($data_distributor);
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
            $account = Account::where('code', '1111')->first();
            // $journal = Journal::whereDate('journal_date', date('Y-m-d'))->where('type', 'good_loading')->where('credit_account_id', $account->id)->first();

            // if($journal != null)
            // {
            //     $data_journal['debit'] = floatval($journal->debit) + floatval(unformatNumber($request->total_item_price));
            //     $data_journal['credit'] = floatval($journal->credit) + floatval(unformatNumber($request->total_item_price));

            //     $journal->update($data_journal);
            // }
            // else
            // {
                $data_journal['type']               = 'good_loading';
                $data_journal['journal_date']       = date('Y-m-d');
                $data_journal['name']               = 'Loading barang ' . $good_loading->distributor->name . ' tanggal ' . displayDate($good_loading->loading_date);
                $data_journal['debit_account_id']   = Account::where('code', '1141')->first()->id;
                $data_journal['debit']              = unformatNumber($request->total_item_price);
                $data_journal['credit_account_id']  = $account->id;
                $data_journal['credit']             = unformatNumber($request->total_item_price);

                Journal::create($data_journal);
            // }

            return $good_loading;
        }
    }
}
