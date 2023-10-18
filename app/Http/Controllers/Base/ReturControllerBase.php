<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Account;
use App\Models\Good;
use App\Models\GoodLoading;
use App\Models\GoodLoadingDetail;
use App\Models\GoodUnit;
use App\Models\Journal;
use App\Models\ReturItem;
use App\Models\Transaction;
use App\Models\TransactionDetail;

trait ReturControllerBase 
{
    public function indexReturBase($distributor_id, $status, $pagination)
    {
        if($status == 'null') $status = null;

        if($distributor_id == 'all')
        {
            if($pagination == 'all')
            {
                if($status == null)
                {
                    $items = ReturItem::select(DB::raw('COUNT(retur_items.id) as total'), 'distributors.name as distributor_name', 'goods.name as good_name', 'units.name as unit_name', 'retur_items.created_at', 'retur_items.good_unit_id as id')
                                      ->join('goods', 'goods.id', 'retur_items.good_id')
                                      ->join('distributors', 'distributors.id', 'retur_items.last_distributor_id')
                                      ->join('good_units', 'good_units.id', 'retur_items.good_unit_id')
                                      ->join('units', 'units.id', 'good_units.unit_id')
                                      ->where('returned_type', null)
                                      ->groupBy('retur_items.good_unit_id', 'distributors.name', 'goods.name', 'retur_items.created_at', 'units.name')->get();
                }
                else
                    $items = ReturItem::where('returned_type', $status)->get();
            }
            else
            {
                if($status == null)
                {
                    $items = ReturItem::select(DB::raw('COUNT(retur_items.id) as total'), 'distributors.name as distributor_name', 'goods.name as good_name', 'units.name as unit_name', 'retur_items.created_at', 'retur_items.good_unit_id as id')
                                      ->join('goods', 'goods.id', 'retur_items.good_id')
                                      ->join('distributors', 'distributors.id', 'retur_items.last_distributor_id')
                                      ->join('good_units', 'good_units.id', 'retur_items.good_unit_id')
                                      ->join('units', 'units.id', 'good_units.unit_id')
                                      ->where('returned_type', null)
                                      ->groupBy('retur_items.good_unit_id', 'distributors.name', 'goods.name', 'retur_items.created_at', 'units.name')->paginate($pagination);
                }
                else
                    $items = ReturItem::where('returned_type', $status)->paginate($pagination);
            }
        }
        else
        {
            if($pagination == 'all')
            {
                $items = ReturItem::where('last_distributor_id', $distributor_id)
                                  ->where('returned_type', $status)
                                  ->get();
            }
            else
            {
                $items = ReturItem::where('last_distributor_id', $distributor_id)
                                  ->where('returned_type', $status)
                                  ->paginate($pagination);
            }
        }

        return $items;
    }

    public function storeReturBase($role, $role_id, Request $request)
    {  
        for ($i = 0; $i < sizeof($request->names) ; $i++) 
        { 
            if($request->names[$i] != null)
            {
                $good = Good::find($request->names[$i]);

                $good_unit = GoodUnit::where('good_id', $request->names[$i])
                                     ->where('unit_id', $request->units[$i])
                                     ->first();  

                $data_transaction['role']             = $role;
                $data_transaction['role_id']          = $role_id;
                $data_transaction['member_id']        = null;
                $data_transaction['type']             = 'retur_item';
                $data_transaction['total_item_price'] = unformatNumber($good_unit->selling_price * $request->quantities[$i]);
                $data_transaction['total_discount_price'] = unformatNumber(0);
                $data_transaction['total_sum_price'] = $data_transaction['total_item_price'];
                $data_transaction['money_paid'] = unformatNumber(0);
                $data_transaction['money_returned'] = unformatNumber(0);
                $data_transaction['store']   = 'ntn getasan';
                $data_transaction['payment'] = 'cash';
                $data_transaction['note']    = 'Tambah barang retur';

                $transaction = Transaction::create($data_transaction);

                $data_detail_retur['transaction_id'] = $transaction->id;
                $data_detail_retur['good_unit_id']   = $good_unit->id;
                $data_detail_retur['type']           = $data_transaction['type'];
                $data_detail_retur['quantity']       = $request->quantities[$i];
                $data_detail_retur['real_quantity']  = $request->quantities[$i] * $good_unit->unit->quantity;
                $data_detail_retur['buy_price']      = unformatNumber($good_unit->buy_price);
                $data_detail_retur['selling_price']  = unformatNumber($good_unit->selling_price);
                $data_detail_retur['discount_price'] = unformatNumber(0);
                $data_detail_retur['sum_price']      = unformatNumber($data_transaction['total_item_price']);

                TransactionDetail::create($data_detail_retur);

                for($j = 0; $j < $data_detail_retur['quantity']; $j++)
                {
                    $data_retur['good_id'] = $good->id;
                    $data_retur['good_unit_id'] = $good_unit->id;
                    $data_retur['last_distributor_id'] = $request->distributor_id;

                    ReturItem::create($data_retur);
                }
            }
        }

        return true;
    }

    public function returItemReturBase($item_id, Request $request)
    {
        $data = $request->input();
        // dd($data);die;
        // dd( $data['qty-' . $request->type . '-' . $item_id]);die;
        $data_item['returned_date'] = date('Y-m-d');
        $data_item['returned_type'] = $request->type;

        for($i = 0; $i < $data['qty-' . $request->type . '-' . $item_id]; $i++)
        {
            $item = ReturItem::where('good_unit_id', $item_id)
                             ->where('returned_date', null)
                             ->orderBy('id', 'asc')
                             ->first();
            $item->update($data_item);
        }

        if($request->type == 'uang')
        {
            $data_journal['type']               = 'retur';
            $data_journal['journal_date']       = date('Y-m-d');
            $data_journal['name']               = 'Retur barang ' . $item->good->name . ' dengan uang tanggal ' . displayDate(date('Y-m-d'));
            $data_journal['debit_account_id']   = Account::where('code', '1111')->first()->id;
            $data_journal['debit']              = $item->good_unit->buy_price * $data['qty-' . $request->type . '-' . $item_id];
            $data_journal['credit_account_id']  = Account::where('code', '1141')->first()->id;
            $data_journal['credit']             = $data_journal['debit'];

            Journal::create($data_journal);
        }
        else
        {
            $data_loading['role']         = 'admin';
            $data_loading['role_id']      = \Auth::user()->id;
            $data_loading['checker']      = 'Load by sistem';
            $data_loading['loading_date'] = date('Y-m-d');
            $data_loading['distributor_id']   = $item->good->getLastBuy()->good_loading->distributor->id;
            $data_loading['total_item_price'] = unformatNumber($item->good_unit->buy_price * $data['qty-' . $request->type . '-' . $item_id]);
            $data_loading['note']             = 'Loading barang retur ' . $item->good->name . ' (berupa barang)';
            $data_loading['payment']          = 'cash';

            $good_loading = GoodLoading::create($data_loading);

            $data_detail['good_loading_id'] = $good_loading->id;
            $data_detail['good_unit_id']    = $item->good_unit->id;
            $data_detail['last_stock']      = $item->good->getStock();
            $data_detail['quantity']        = $data['qty-' . $request->type . '-' . $item_id];
            $data_detail['real_quantity']   = $data['qty-' . $request->type . '-' . $item_id] * $item->good_unit->unit->quantity;
            $data_detail['price']           = unformatNumber($item->good_unit->buy_price);
            $data_detail['selling_price']   = unformatNumber($item->good_unit->selling_price);
            $data_detail['expiry_date']     = null;

            GoodLoadingDetail::create($data_detail);

            $data_journal_loading_retur['type']               = 'good_loading';
            $data_journal_loading_retur['journal_date']       = date('Y-m-d');
            $data_journal_loading_retur['name']               = 'Loading barang retur ' . $item->good->name . ' (dari distributor berupa barang) tanggal ' . displayDate(date('Y-m-d'));
            $data_journal_loading_retur['debit_account_id']   = Account::where('code', '1141')->first()->id;
            $data_journal_loading_retur['debit']              = unformatNumber($data_loading['total_item_price']);
            $data_journal_loading_retur['credit_account_id']  = Account::where('code', '1141')->first()->id;
            $data_journal_loading_retur['credit']             = unformatNumber($data_loading['total_item_price']);

            Journal::create($data_journal_loading_retur);
        }

        return true;
    }
}
