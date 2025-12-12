<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class Distributor extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'name', 'location', 'total_aset', 'total_profit', 'total_rugi'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];

    public function totalHutangDagangInternal($pagination)
    {
        if($pagination == 'all')
            $journals = Journal::where('type', 'hutang dagang ' . $this->id)
                               ->orderBy('id', 'desc')
                               ->get();
        else
            $journals = Journal::where('type', 'hutang dagang ' . $this->id)
                               ->orderBy('id', 'desc')
                               ->paginate($pagination);

        return $journals;
    }

    public function totalHutangDagangLoading($pagination)
    {
        if($pagination == 'all')
            $good_loadings = GoodLoading::where('payment', '2101')
                                        ->where('distributor_id', $this->id)
                                        ->orderBy('id', 'desc')
                                        ->get();
        else
            $good_loadings = GoodLoading::where('payment', '2101')
                                        ->where('distributor_id', $this->id)
                                        ->orderBy('id', 'desc')
                                        ->paginate($pagination);

        return $good_loadings;
    }

    public function totalPiutangDagangInternal($pagination)
    {
        if($pagination == 'all')
            $journals = Journal::where('type', 'piutang dagang ' . $this->id)
                               ->get();
        else
            $journals = Journal::where('type', 'piutang dagang ' . $this->id)
                               ->paginate($pagination);

        return $journals;
    }

    public function totalPiutangDagangLoading($pagination)
    {
        if($pagination == 'all')
            $good_loadings = GoodLoading::where('payment', '1131')
                                        ->where('distributor_id', $this->id)
                                        ->orderBy('id', 'desc')
                                        ->get();
        else
            $good_loadings = GoodLoading::where('payment', '1131')
                                        ->where('distributor_id', $this->id)
                                        ->orderBy('id', 'desc')
                                        ->paginate($pagination);

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
          $goods = Good::join('good_units', 'good_units.id', 'goods.base_unit_id')
                       ->selectRaw('SUM(goods.last_stock * good_units.buy_price) as total')
                       ->where('last_distributor_id', $this->id)
                       ->get();

          return $goods[0]->total;
    }

    public function detailAssetFromGood($pagination)
    {
        $goods = Good::join('good_units', 'good_units.id', 'goods.base_unit_id')
                     ->selectRaw('SUM(goods.last_stock * good_units.buy_price) as total, goods.id, goods.name, goods.last_loading, goods.last_transaction, goods.total_loading, goods.total_transaction, goods.last_stock')
                     ->where('last_distributor_id', $this->id)
                     ->groupBy('goods.id', 'goods.name', 'goods.last_loading', 'goods.last_transaction', 'goods.total_loading', 'goods.total_transaction', 'goods.last_stock')
                     ->orderBy('total', 'desc')
                     ->orderBy('last_transaction', 'desc')
                     ->orderBy('last_loading', 'desc')
                     ->paginate($pagination);

        return $goods;
    }

    public function detailAsset($pagination)
    {
          $perPage = 20;
          $currentPage = request('page', 1);

          $totalCount = Good::where('last_distributor_id', $this->id)->count();

          $basicQuery = DB::select(DB::raw("SELECT goods.id, goods.name, recap.total_loading, recap.total_transaction, SUM(recap.total_loading - recap.total_transaction) as total_real, recap.real_price, SUM((recap.total_loading - recap.total_transaction) * recap.real_price) as money_stock, last_loading.loading_date as loading_date, last_transaction.transaction_date as transaction_date
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
                                 ORDER BY money_stock DESC
                                 LIMIT " . $perPage . " OFFSET " . $pagination));

          $results = $basicQuery;

          $paginator = new \Illuminate\Pagination\LengthAwarePaginator($results, $totalCount, $perPage, $currentPage, ['path' => url('/admin/distributor/' . $this->id . '/detail/aset')]);

          return $paginator;
    }

    public function detailAsset2()
    {
        $goods = Good::where('last_distributor_id', $this->id)->orderBy('');paginate(20);

        return $goods;
    }

    public function totalOutcome($pagination)
    {
        if($pagination == 'all')
            $journals = Journal::where('type', 'credit_payment')
                               ->where('name', 'like', 'Pembayaran hutang ' . $this->name . ' (ID ' . $this->id . ')%')
                               ->get();
        else
            $journals = Journal::where('type', 'credit_payment')
                               ->where('name', 'like', 'Pembayaran hutang ' . $this->name . ' (ID ' . $this->id . ')%')
                               ->paginate($pagination);

        return $journals;
    }

    public function titipUang($pagination)
    {
        if($pagination == 'all')
            $journals = Journal::where('type', 'cash_transaction')
                               ->where('name', 'like', 'Titipan Uang Pembayaran ' . $this->name . '%')
                               ->get();
        else
            $journals = Journal::where('type', 'cash_transaction')
                               ->where('name', 'like', 'Titipan Uang Pembayaran ' . $this->name . '%')
                               ->paginate($pagination);

        return $journals;
    }

    public function totalUntung($pagination)
    {
          $perPage = 20;
          $currentPage = request('page', 1);

        if($pagination == 'all')
            $paginator = TransactionDetail::join('good_units', 'good_units.id', 'transaction_details.good_unit_id')
                                    ->join('transactions', 'transactions.id', 'transaction_details.transaction_id')
                                    ->join('goods', 'goods.id', 'good_units.good_id')
                                    ->join('units', 'units.id', 'good_units.unit_id')
                                    ->select(DB::raw('SUM((transaction_details.selling_price - transaction_details.buy_price) * transaction_details.quantity) AS total'))
                                    ->where('goods.last_distributor_id', $this->id)
                                    ->where('transaction_details.type', 'normal')
                                    ->where('transactions.deleted_at', null)
                                    // ->where('good_units.deleted_at', null)
                                    ->where('goods.deleted_at', null)
                                    ->get();
        else
        {
            $results = DB::select(DB::raw("SELECT goods.id, goods.name, SUM(total_transaction.total) as total, SUM(total_transaction.qty) as quantity
                                 FROM goods 
                                 LEFT JOIN (SELECT goods.id, SUM((transaction_details.selling_price - transaction_details.buy_price) * transaction_details.quantity) AS total, transaction_details.quantity as qty
                                      FROM transaction_details
                                      JOIN transactions ON transactions.id = transaction_details.transaction_id
                                      JOIN good_units ON good_units.id = transaction_details.good_unit_id
                                      JOIN goods ON goods.id = good_units.good_id
                                      WHERE transactions.deleted_at IS NULL AND goods.last_distributor_id = " . $this->id . " AND transaction_details.type = 'normal' AND goods.deleted_at IS NULL
                                      GROUP BY goods.id, transaction_details.quantity, transaction_details.buy_price, transaction_details.selling_price) as total_transaction ON total_transaction.id = goods.id
                                 WHERE goods.id = total_transaction.id
                                 GROUP BY goods.id, goods.name
                                 ORDER BY total DESC
                                 LIMIT " . $perPage . " OFFSET " . (($currentPage - 1) * $perPage)));

            $data = DB::select(DB::raw("SELECT goods.id, goods.name, SUM(total_transaction.total) as total, SUM(total_transaction.qty) as quantity
                                 FROM goods 
                                 LEFT JOIN (SELECT goods.id, SUM((transaction_details.selling_price - transaction_details.buy_price) * transaction_details.quantity) AS total, transaction_details.quantity as qty
                                      FROM transaction_details
                                      JOIN transactions ON transactions.id = transaction_details.transaction_id
                                      JOIN good_units ON good_units.id = transaction_details.good_unit_id
                                      JOIN goods ON goods.id = good_units.good_id
                                      WHERE transactions.deleted_at IS NULL AND goods.last_distributor_id = " . $this->id . " AND transaction_details.type = 'normal' AND goods.deleted_at IS NULL
                                      GROUP BY goods.id, transaction_details.quantity, transaction_details.buy_price, transaction_details.selling_price) as total_transaction ON total_transaction.id = goods.id
                                 WHERE goods.id = total_transaction.id
                                 GROUP BY goods.id, goods.name
                                 ORDER BY total DESC"));

          $totalCount = sizeof($data);

          $paginator = new \Illuminate\Pagination\LengthAwarePaginator($results, $totalCount, $perPage, $currentPage, ['path' => url('/admin/distributor/' . $this->id . '/detail/untung')]);
      }

        return $paginator;
    }

    public function totalRugi($pagination)
    {
      $perPage = 20;
      $currentPage = request('page', 1);

      $data = TransactionDetail::join('good_units', 'good_units.id', 'transaction_details.good_unit_id')
                                    ->join('transactions', 'transactions.id', 'transaction_details.transaction_id')
                                    ->join('goods', 'goods.id', 'good_units.good_id')
                                    ->join('units', 'units.id', 'good_units.unit_id')
                                    ->select(DB::raw('SUM(transaction_details.buy_price * transaction_details.quantity) AS total'))
                                    ->whereRaw('goods.last_distributor_id = ' . $this->id . ' AND (transaction_details.type = "5215" OR transaction_details.type = "stock_opname") AND transactions.deleted_at is NULL AND goods.deleted_at is NULL')
                                    ->where('transactions.deleted_at', null)
                                    ->get();

        if($pagination == 'all')
            $paginator = $data;
        else
        {

            $results = DB::select(DB::raw("SELECT goods.id, goods.name, SUM(total_transaction.total) as total, SUM(total_transaction.qty) as quantity
                                 FROM goods 
                                 LEFT JOIN (SELECT goods.id, SUM(transaction_details.buy_price * transaction_details.quantity) AS total, transaction_details.quantity as qty
                                      FROM transaction_details
                                      JOIN transactions ON transactions.id = transaction_details.transaction_id
                                      JOIN good_units ON good_units.id = transaction_details.good_unit_id
                                      JOIN goods ON goods.id = good_units.good_id
                                      WHERE goods.last_distributor_id = " . $this->id . " AND (transaction_details.type = '5215' OR transaction_details.type = 'stock_opname') AND transactions.deleted_at is NULL AND goods.deleted_at is NULL
                                      GROUP BY goods.id, transaction_details.quantity, transaction_details.buy_price) as total_transaction ON total_transaction.id = goods.id
                                 WHERE goods.id = total_transaction.id
                                 GROUP BY goods.id, goods.name
                                 ORDER BY total DESC
                                 LIMIT " . $perPage . " OFFSET " . (($currentPage - 1) * $perPage)));

            $data = DB::select(DB::raw("SELECT goods.id, goods.name, SUM(total_transaction.total) as total, SUM(total_transaction.qty) as quantity
                                 FROM goods 
                                 LEFT JOIN (SELECT goods.id, SUM(transaction_details.buy_price * transaction_details.quantity) AS total, transaction_details.quantity as qty
                                      FROM transaction_details
                                      JOIN transactions ON transactions.id = transaction_details.transaction_id
                                      JOIN good_units ON good_units.id = transaction_details.good_unit_id
                                      JOIN goods ON goods.id = good_units.good_id
                                      WHERE goods.last_distributor_id = " . $this->id . " AND (transaction_details.type = '5215' OR transaction_details.type = 'stock_opname') AND transactions.deleted_at is NULL AND goods.deleted_at is NULL
                                      GROUP BY goods.id, transaction_details.quantity, transaction_details.buy_price) as total_transaction ON total_transaction.id = goods.id
                                 WHERE goods.id = total_transaction.id
                                 GROUP BY goods.id, goods.name"));

            $totalCount = sizeof($data);


          $paginator = new \Illuminate\Pagination\LengthAwarePaginator($results, $totalCount, $perPage, $currentPage, ['path' => url('/admin/distributor/' . $this->id . '/detail/untung')]);
            // $transaction = TransactionDetail::join('good_units', 'good_units.id', 'transaction_details.good_unit_id')
            //                         ->join('transactions', 'transactions.id', 'transaction_details.transaction_id')
            //                         ->join('goods', 'goods.id', 'good_units.good_id')
            //                         ->join('units', 'units.id', 'good_units.unit_id')
            //                         ->select(DB::raw('SUM(transaction_details.buy_price * transaction_details.quantity) AS total, transactions.id, transaction_details.type, transaction_details.created_at, goods.name, transaction_details.quantity, transaction_details.buy_price, transaction_details.selling_price, units.code'))
            //                         ->whereRaw('goods.last_distributor_id = ' . $this->id . ' AND (transaction_details.type = "5215" OR transaction_details.type = "stock_opname") AND transactions.deleted_at is NULL AND goods.deleted_at is NULL')
            //                         ->groupBy('transactions.id')
            //                         ->groupBy('transaction_details.type')
            //                         ->groupBy('transaction_details.created_at')
            //                         ->groupBy('goods.name')
            //                         ->groupBy('transaction_details.quantity')
            //                         ->groupBy('transaction_details.buy_price')
            //                         ->groupBy('transaction_details.selling_price')
            //                         ->groupBy('units.code')
            //                         ->orderBy('total', 'desc')
            //                         ->paginate($pagination);
        }

        return $paginator;
    }

    public function getLedgers($pagination)
    {
        $ledgers = DistributorLedger::where('distributor_id', $this->id)
                                    ->orderBy('created_at', 'DESC')->paginate($pagination);

        return $ledgers;
    }
}
