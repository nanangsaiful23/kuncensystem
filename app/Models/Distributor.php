<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Distributor extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'name', 'location'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];

    public function totalHutangDagangInternal()
    {
        $journals = Journal::where('type', 'hutang dagang ' . $this->id)
                           ->orderBy('id', 'desc')
                           ->get();

        return $journals;
    }

    public function totalHutangDagangLoading()
    {
        $good_loadings = GoodLoading::where('payment', '2101')
                                    ->where('distributor_id', $this->id)
                                    ->orderBy('id', 'desc')
                                    ->get();

        return $good_loadings;
    }

    public function totalPiutangDagangInternal()
    {
        $journals = Journal::where('type', 'piutang dagang ' . $this->id)
                           ->get();

        return $journals;
    }

    public function totalPiutangDagangLoading()
    {
        $good_loadings = GoodLoading::where('payment', '1131')
                                    ->where('distributor_id', $this->id)
                                    ->orderBy('id', 'desc')
                                    ->get();

        return $good_loadings;
    }

    public function loadings()
    {
        return GoodLoadingDetail::join('good_units', 'good_units.id', 'good_loading_details.good_unit_id')
                                ->join('good_loadings', 'good_loadings.id', 'good_loading_details.good_loading_id')
                                ->join('goods', 'goods.id', 'good_units.good_id')
                                ->select(DB::raw('SUM(good_loading_details.quantity) AS total'))
                                ->where('goods.last_distributor_id', $this->id)
                                ->where('good_loadings.deleted_at', null)
                                // ->where('good_units.deleted_at', null)
                                ->where('goods.deleted_at', null)
                                ->get();
    }
    
    public function transactions()
    {
        return TransactionDetail::join('good_units', 'good_units.id', 'transaction_details.good_unit_id')
                                ->join('transactions', 'transactions.id', 'transaction_details.transaction_id')
                                ->join('goods', 'goods.id', 'good_units.good_id')
                                ->select(DB::raw('SUM(transaction_details.quantity) AS total'))
                                ->where('goods.last_distributor_id', $this->id)
                                ->where('transaction_details.type', '!=', 'retur')
                                ->where('transactions.deleted_at', null)
                                // ->where('good_units.deleted_at', null)
                                ->where('goods.deleted_at', null)
                                ->get();
    }

    public function getAsset()
    {
          $goods = Good::where('last_distributor_id', $this->id)->get();

          $total = 0;
          foreach($goods as $good)
          {
               $total += $good->getStock() * $good->getPcsSellingPrice()->buy_price;
          }

          return $total;
    }

    public function detailAsset()
    {
        $result = DB::select(DB::raw("SELECT goods.id, goods.name, recap.total_loading, recap.total_transaction, SUM(recap.total_loading - recap.total_transaction) as total_real, recap.real_price, SUM((recap.total_loading - recap.total_transaction) * recap.real_price) as money_stock, last_loading.loading_date as loading_date, last_transaction.transaction_date as transaction_date
                                 FROM goods 
                                 LEFT JOIN (SELECT goods.id, good_loadings.loading_date as loading_date
                                      FROM good_loading_details
                                      JOIN good_loadings ON good_loadings.id = good_loading_details.good_loading_id
                                      JOIN good_units ON good_units.id = good_loading_details.good_unit_id
                                      JOIN goods ON goods.id = good_units.good_id
                                      WHERE good_loading_details.deleted_at IS NULL AND good_loadings.deleted_at IS NULL AND goods.deleted_at IS NULL AND good_loadings.loading_date IS NOT NULL
                                      GROUP BY goods.id, good_loadings.loading_date
                                      ORDER BY good_loadings.id DESC
                                      LIMIT 1) as last_loading ON last_loading.id = goods.id
                                 LEFT JOIN (SELECT goods.id, transaction_details.created_at as transaction_date
                                      FROM transaction_details
                                      JOIN transactions ON transactions.id = transaction_details.transaction_id
                                      JOIN good_units ON good_units.id = transaction_details.good_unit_id
                                      RIGHT JOIN goods ON goods.id = good_units.good_id
                                      WHERE transaction_details.deleted_at IS NULL AND transactions.deleted_at IS NULL AND goods.deleted_at IS NULL AND transaction_details.type != 'retur'
                                      GROUP BY goods.id, transaction_details.created_at
                                      ORDER BY transaction_details.created_at DESC
                                      LIMIT 1) as last_transaction ON last_transaction.id = goods.id
                                 LEFT JOIN(SELECT goods.id, COALESCE(loading.total_loading, 0) as total_loading, COALESCE(transaction.total_transaction, 0) as total_transaction, price.real_price
                                     FROM goods
                                     LEFT JOIN (SELECT goods.id, coalesce(SUM(good_loading_details.real_quantity), 0) AS total_loading
                                      FROM good_loading_details
                                      JOIN good_loadings ON good_loadings.id = good_loading_details.good_loading_id
                                      JOIN good_units ON good_units.id = good_loading_details.good_unit_id
                                      JOIN goods ON goods.id = good_units.good_id
                                      WHERE good_loading_details.deleted_at IS NULL AND good_loadings.deleted_at IS NULL AND goods.deleted_at IS NULL 
                                      GROUP BY goods.id) as loading ON loading.id = goods.id
                                     LEFT JOIN (SELECT goods.id, coalesce(SUM(transaction_details.real_quantity), 0) AS total_transaction
                                      FROM transaction_details
                                      JOIN transactions ON transactions.id = transaction_details.transaction_id
                                      JOIN good_units ON good_units.id = transaction_details.good_unit_id
                                      RIGHT JOIN goods ON goods.id = good_units.good_id
                                      WHERE transaction_details.deleted_at IS NULL AND transactions.deleted_at IS NULL AND goods.deleted_at IS NULL AND transaction_details.type != 'retur'
                                      GROUP BY goods.id) as transaction ON transaction.id = goods.id
                                     LEFT JOIN (SELECT goods.id, good_units.buy_price, units.quantity, good_units.buy_price/units.quantity as real_price
                                      FROM good_units
                                      RIGHT JOIN goods ON goods.id = good_units.good_id
                                      JOIN units ON units.id = good_units.unit_id
                                      WHERE good_units.deleted_at IS NULL
                                      GROUP BY goods.id, good_units.buy_price, units.quantity) as price ON price.id = goods.id
                                     WHERE goods.last_distributor_id = " . $this->id . "
                                     GROUP BY goods.id, goods.name, total_loading, total_transaction, real_price) as recap ON recap.id = goods.id
                                 WHERE goods.last_distributor_id = " . $this->id . "
                                 GROUP BY goods.id, goods.name, recap.total_loading, recap.total_transaction, recap.real_price, last_loading.loading_date, last_transaction.transaction_date
                                 ORDER BY money_stock DESC"));

        return $result;
    }

    public function detailAsset2()
    {
        $goods = Good::where('last_distributor_id', $this->id)->get();

        return $goods;
    }

    public function totalOutcome()
    {
        $journals = Journal::where('type', 'credit_payment')
                           ->where('name', 'like', 'Pembayaran hutang ' . $this->name . ' (ID ' . $this->id . ')%')
                           ->get();

        return $journals;
    }

    public function titipUang()
    {
        $journals = Journal::where('type', 'cash_transaction')
                           ->where('name', 'like', 'Titipan Uang Pembayaran ' . $this->name . '%')
                           ->get();

        return $journals;
    }
}
