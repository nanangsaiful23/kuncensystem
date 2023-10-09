<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\Account;
use App\Models\Good;
use App\Models\GoodLoading;
use App\Models\GoodLoadingDetail;
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
                $items = ReturItem::where('returned_type', $status)->get();
            }
            else
            {
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
        $good = Good::find($request->good_id);

        $data_transaction['role']             = $role;
        $data_transaction['role_id']          = $role_id;
        $data_transaction['member_id']        = null;
        $data_transaction['type']             = 'retur_item';
        $data_transaction['total_item_price'] = unformatNumber($good->getPcsSellingPrice()->selling_price * $request->quantity);
        $data_transaction['total_discount_price'] = unformatNumber(0);
        $data_transaction['total_sum_price'] = $data_transaction['total_item_price'];
        $data_transaction['money_paid'] = unformatNumber(0);
        $data_transaction['money_returned'] = unformatNumber(0);
        $data_transaction['store']   = 'ntn getasan';
        $data_transaction['payment'] = 'cash';
        $data_transaction['note']    = 'Tambah barang retur';

        $transaction = Transaction::create($data_transaction);

        // $data_journal['type']               = $data_transaction['type'];
        // $data_journal['journal_date']       = date('Y-m-d');
        // $data_journal['name']               = 'Pengembalian/retur barang ke distributor (ID barang ' . $good->id . ' tanggal ' . displayDate(date('Y-m-d'));
        // $data_journal['debit_account_id']  = Account::where('code', '1111')->first()->id;
        // $data_journal['debit']              = $data_transaction['total_sum_price'];
        // $data_journal['credit_account_id']  = Account::where('code', '4101')->first()->id;
        // $data_journal['credit']             = $data_transaction['total_sum_price'];

        // Journal::create($data_journal);

        $data_detail_retur['transaction_id'] = $transaction->id;
        $data_detail_retur['good_unit_id']   = $good->getPcsSellingPrice()->id;
        $data_detail_retur['type']           = $data_transaction['type'];
        $data_detail_retur['quantity']       = $request->quantity;
        $data_detail_retur['real_quantity']  = $request->quantity * $good->getPcsSellingPrice()->unit->quantity;
        $data_detail_retur['buy_price']      = unformatNumber($good->getPcsSellingPrice()->buy_price);
        $data_detail_retur['selling_price']  = unformatNumber($good->getPcsSellingPrice()->selling_price);
        $data_detail_retur['discount_price'] = unformatNumber(0);
        $data_detail_retur['sum_price']      = unformatNumber($data_transaction['total_item_price']);

        TransactionDetail::create($data_detail_retur);

        for($j = 0; $j < $data_detail_retur['real_quantity']; $j++)
        {
            $data_retur['good_id'] = $good->id;
            $data_retur['last_distributor_id'] = $request->distributor_id;

            ReturItem::create($data_retur);
        }

        return true;
    }

    public function returItemReturBase($item_id, Request $request)
    {
        $data_item['returned_date'] = date('Y-m-d');
        $data_item['returned_type'] = $request->type;

        $item = ReturItem::find($item_id);
        $item->update($data_item);

        if($request->type == 'uang')
        {
            $data_journal['type']               = 'retur';
            $data_journal['journal_date']       = date('Y-m-d');
            $data_journal['name']               = 'Retur barang ' . $item->good->name . ' dengan uang tanggal ' . displayDate(date('Y-m-d'));
            $data_journal['debit_account_id']   = Account::where('code', '1111')->first()->id;
            $data_journal['debit']              = $item->good->getPcsSellingPrice()->buy_price;
            $data_journal['credit_account_id']  = Account::where('code', '1141')->first()->id;
            $data_journal['credit']             = $item->good->getPcsSellingPrice()->buy_price;

            Journal::create($data_journal);
        }
        else
        {
            $data_loading['role']         = 'admin';
            $data_loading['role_id']      = \Auth::user()->id;
            $data_loading['checker']      = 'Load by sistem';
            $data_loading['loading_date'] = date('Y-m-d');
            $data_loading['distributor_id']   = $item->good->getLastBuy()->good_loading->distributor->id;
            $data_loading['total_item_price'] = unformatNumber($item->good->getPcsSellingPrice()->buy_price);
            $data_loading['note']             = 'Loading barang retur (berupa barang)';
            $data_loading['payment']          = 'cash';

            $good_loading = GoodLoading::create($data_loading);

            $data_detail['good_loading_id'] = $good_loading->id;
            $data_detail['good_unit_id']    = $item->good->getPcsSellingPrice()->id;
            $data_detail['last_stock']      = $item->good->getStock();
            $data_detail['quantity']        = 1;
            $data_detail['real_quantity']   = 1;
            $data_detail['price']           = unformatNumber($item->good->getPcsSellingPrice()->buy_price);
            $data_detail['selling_price']   = unformatNumber($item->good->getPcsSellingPrice()->selling_price);
            $data_detail['expiry_date']     = null;

            GoodLoadingDetail::create($data_detail);

            $data_journal_loading_retur['type']               = 'good_loading';
            $data_journal_loading_retur['journal_date']       = date('Y-m-d');
            $data_journal_loading_retur['name']               = 'Loading barang retur (dari distributor berupa barang) tanggal ' . displayDate(date('Y-m-d'));
            $data_journal_loading_retur['debit_account_id']   = Account::where('code', '1141')->first()->id;
            $data_journal_loading_retur['debit']              = unformatNumber($data_loading['total_item_price']);
            $data_journal_loading_retur['credit_account_id']  = Account::where('code', '1141')->first()->id;
            $data_journal_loading_retur['credit']             = unformatNumber($data_loading['total_item_price']);

            Journal::create($data_journal_loading_retur);
        }

        return true;
    }
}
