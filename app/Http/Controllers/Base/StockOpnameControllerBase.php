<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Account;
use App\Models\Distributor;
use App\Models\GoodLoading;
use App\Models\GoodLoadingDetail;
use App\Models\GoodUnit;
use App\Models\Journal;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use App\Models\Transaction;
use App\Models\TransactionDetail;

trait StockOpnameControllerBase 
{
    public function indexStockOpnameBase($start_date, $end_date, $pagination)
    {
        if($pagination == 'all')
        $stock_opnames = StockOpname::whereDate('stock_opnames.created_at', '>=', $start_date)
                                    ->whereDate('stock_opnames.created_at', '<=', $end_date) 
                                    ->orderBy('stock_opnames.created_at','asc')
                                    ->get();
        else
        $stock_opnames = StockOpname::whereDate('stock_opnames.created_at', '>=', $start_date)
                                    ->whereDate('stock_opnames.created_at', '<=', $end_date)
                                    ->orderBy('stock_opnames.created_at','asc')
                                    ->paginate($pagination);

        return $stock_opnames;
    }

    public function storeStockOpnameBase($role, $role_id, Request $request)
    {
        // dd($request);die;
        $data_stock_opname['role']    = $role;
        $data_stock_opname['role_id'] = $role_id;
        $data_stock_opname['note']    = $request->note;
        $data_stock_opname['checker'] = $request->checker;

        $stock_opname = StockOpname::create($data_stock_opname);

        $total = 0;
        for($i = 0; $i < sizeof($request->names); $i++)
        {
            if($request->names[$i] != null)
            {
                $good_unit = GoodUnit::where('good_id', $request->names[$i])
                                     ->where('unit_id', $request->units[$i])
                                     ->first();

                $data_stock_opname_detail['stock_opname_id'] = $stock_opname->id;
                $data_stock_opname_detail['good_unit_id'] = $good_unit->id;
                $data_stock_opname_detail['old_stock'] = $request->old_stocks[$i];
                $data_stock_opname_detail['new_stock'] = $request->new_stocks[$i];

                if($request->old_stocks[$i] < $request->new_stocks[$i])
                {
                    $distributor = Distributor::where('name', 'Stock Opname')->first();

                    if($distributor == null)
                    {
                        $data_distributor['name'] = 'Stock Opname';

                        $distributor = Distributor::create($data_distributor);
                    }
                    $data_loading['distributor_id'] = $distributor->id;

                    $data_loading['role']         = $role;
                    $data_loading['role_id']      = $role_id;
                    $data_loading['checker']      = $request->checker;
                    $data_loading['loading_date'] = date('Y-m-d');
                    $data_loading['total_item_price'] = $good_unit->buy_price * ($request->new_stocks[$i] - $request->old_stocks[$i]);
                    $data_loading['note']         = $request->note . ' (stock opname by system)';
                    $data_loading['payment']      = 'by system';

                    $good_loading = GoodLoading::create($data_loading);

                    $data_detail['good_loading_id'] = $good_loading->id;
                    $data_detail['good_unit_id']    = $good_unit->id;
                    $data_detail['last_stock']      = $request->old_stocks[$i];
                    $data_detail['quantity']        = ($request->new_stocks[$i] - $request->old_stocks[$i]);
                    $data_detail['real_quantity']   = $data_detail['quantity'] * $good_unit->unit->quantity;
                    $data_detail['price']           = $good_unit->buy_price;
                    $data_detail['selling_price']   = $good_unit->selling_price;
                    $data_detail['expiry_date']     = null;

                    GoodLoadingDetail::create($data_detail);

                    $good = $good_unit->good;

                    $data_good['total_transaction'] = $good->total_transaction;
                    $data_good['total_loading']     = $good->total_loading + round($data_detail['real_quantity'] / $good->base_unit()->unit->quantity, 3);
                    $data_good['last_stock']        = $data_good['total_loading'] - $good->total_transaction;
                    $data_good['last_loading']      = $data_loading['loading_date'];
                    $good->update($data_good);

                    $data_journal['type']               = 'good_loading';
                    $data_journal['type_id']            = $good_loading->id;
                    $data_journal['journal_date']       = date('Y-m-d');
                    $data_journal['name']               = 'Loading barang ' . $good_unit->good->name . ' stock opname tanggal ' . displayDate($good_loading->loading_date);
                    $data_journal['debit_account_id']   = Account::where('code', '1141')->first()->id;
                    $data_journal['debit']              = unformatNumber($good_loading->total_item_price);
                    $data_journal['credit_account_id']  = Account::where('code', '5215')->first()->id;
                    $data_journal['credit']             = unformatNumber($good_loading->total_item_price);

                    Journal::create($data_journal);

                    $total += $good_loading->total_item_price;
                    $data_stock_opname_detail['total'] = $good_loading->total_item_price;
                }
                elseif($request->old_stocks[$i] > $request->new_stocks[$i])
                {
                    $data_transaction['type'] = 'stock_opname';
                    $data_transaction['role'] = $role;
                    $data_transaction['role_id'] = $role_id;
                    $data_transaction['member_id'] = '1';
                    $data_transaction['total_item_price'] = $good_unit->buy_price * ($request->old_stocks[$i] - $request->new_stocks[$i]);
                    $data_transaction['total_discount_price'] = 0;
                    $data_transaction['total_sum_price'] = $data_transaction['total_item_price'];
                    $data_transaction['voucher'] = null;
                    $data_transaction['voucher_nominal'] = null;
                    $data_transaction['money_paid'] = $data_transaction['total_item_price'];
                    $data_transaction['money_returned'] = 0;
                    $data_transaction['store']   = 'ntn getasan';
                    $data_transaction['payment'] = 'stock_opname';
                    $data_transaction['note']    = $request->note . ' (stock opname by system)';

                    $transaction = Transaction::create($data_transaction);

                    $data_detail['transaction_id'] = $transaction->id;
                    $data_detail['good_unit_id']   = $good_unit->id;
                    $data_detail['type']           = $transaction->type;
                    $data_detail['quantity']       = ($request->old_stocks[$i] - $request->new_stocks[$i]);
                    $data_detail['real_quantity']  = $data_detail['quantity'] * $good_unit->unit->quantity;
                    $data_detail['last_stock']     = $request->old_stocks[$i];
                    $data_detail['buy_price']      = $good_unit->buy_price;
                    $data_detail['selling_price']  = $good_unit->buy_price;
                    $data_detail['discount_price'] = null;
                    $data_detail['sum_price']      = $data_transaction['total_sum_price'];

                    TransactionDetail::create($data_detail);

                    $good = $good_unit->good;

                    $data_good['total_loading']     = $good->total_loading;
                    $data_good['total_transaction'] = $good->total_transaction + round($data_detail['real_quantity'] / $good->base_unit()->unit->quantity, 3);
                    $data_good['last_stock']        = $good->total_loading - $data_good['total_transaction'];
                    $data_good['last_transaction']  = date('Y-m-d');
                    $good->update($data_good);

                    $data_journal['type']               = 'transaction';
                    $data_journal['type_id']            = $transaction->id;
                    $data_journal['journal_date']       = date('Y-m-d');
                    $data_journal['name']               = 'Transaksi barang ' . $good_unit->good->name . ' stock opname tanggal ' . displayDate(date('Y-m-d'));
                    $data_journal['debit_account_id']   = Account::where('code', '5215')->first()->id;
                    $data_journal['debit']              = unformatNumber($transaction->total_item_price);
                    $data_journal['credit_account_id']  = Account::where('code', '1141')->first()->id;
                    $data_journal['credit']             = unformatNumber($transaction->total_item_price);

                    Journal::create($data_journal);

                    $total -= $transaction->total_item_price;
                    $data_stock_opname_detail['total'] = $transaction->total_item_price;
                }

                StockOpnameDetail::create($data_stock_opname_detail);
            }
        }

        $data_stock_opname['total'] = $total;
        $stock_opname->update($data_stock_opname);

        return $stock_opname;
    }
}
