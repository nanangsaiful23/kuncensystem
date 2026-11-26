<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\GoodLoadingDetail;
use App\Models\GoodUnit;

class Good extends Model
{    
    use \App\Models\Concerns\SerializesDatesLegacy;

    use SoftDeletes;
    
    protected $fillable = [
        'category_id', 'type_id', 'brand_id', 'code', 'name', 'last_distributor_id', 'base_unit_id', 'total_loading', 'total_transaction', 'last_stock', 'last_loading', 'last_transaction','is_discontinued', 'discontinued_at', 'discontinued_reason', 'discontinued_by',
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];


    protected static function booted(): void
    {
        // Controllers still write their own arithmetic into the stock
        // columns; recalculate them from history after the commit so the
        // stored value always equals getStock() (see StockService).
        static::saved(function (self $good) {
            if ($good->wasChanged(['total_loading', 'total_transaction', 'last_stock', 'base_unit_id'])) {
                \App\Services\StockService::queue($good->id);
            }
        });
    }

    /**
     * Nama barang untuk ditampilkan: "Jenis nama", mis. jenis "Buku" + nama
     * "tulis sidu 58" → "Buku tulis sidu 58". Jenis tidak diulang kalau nama
     * sudah diawali jenisnya ("Pasta gigi Ciptadent"). Pakai ini (atau
     * getFullName / helper formatGoodName) di semua tampilan supaya seragam.
     */
    public static function formatName($type, $name): string
    {
        $name = trim((string) $name);
        $type = trim((string) $type);

        if ($type === '' || $type === '-' || self::startsWithType($name, $type))
            return ucfirst($name);

        return ucfirst(trim(strtolower($type) . ' ' . $name));
    }

    /**
     * Kebalikan formatName: buang jenis di AWAL nama lengkap yang diketik di
     * form (mis. nama di tabel loading) sebelum disimpan ke goods.name.
     * Hanya awalan yang dibuang; kata jenis di tengah nama tetap utuh.
     */
    public static function nameWithoutType($type, $fullName): string
    {
        $fullName = (string) $fullName;
        $type     = trim((string) $type);

        if ($type === '' || $type === '-')
            return $fullName;

        $name = ltrim(lcfirst($fullName));
        // Hanya "Jenis<spasi>sisa": "Kemoceng/norgren" tetap utuh, dan nama
        // yang isinya cuma jenisnya (mis. "Ember") jangan sampai jadi kosong.
        if (self::startsWithType($name, $type) && ctype_space(substr($name, strlen($type), 1))) {
            $rest = ltrim(substr($name, strlen($type)));
            if ($rest !== '')
                $name = $rest;
        }

        return $name;
    }

    /** true bila $name diawali kata $type (tidak peka huruf besar/kecil, harus utuh satu kata). */
    private static function startsWithType(string $name, string $type): bool
    {
        if ($type === '' || strncasecmp($name, $type, strlen($type)) !== 0)
            return false;

        $next = substr($name, strlen($type), 1);

        return $next === '' || ! ctype_alnum($next);
    }

    public function getFullName()
    {
        return self::formatName($this->type_id ? optional($this->type)->name : null, $this->name);
    }

    /**
     * Untuk hasil query mentah (DB::select / join / paginator) yang hanya
     * membawa goods.id + goods.name: ganti kolom nama tiap baris dengan nama
     * lengkap. Jenisnya diambil sekali untuk semua baris, bukan per baris.
     */
    public static function applyFullNames($rows, string $idKey = 'id', string $nameKey = 'name')
    {
        $list = $rows instanceof \Illuminate\Pagination\AbstractPaginator ? $rows->getCollection() : collect($rows);

        $ids = $list->pluck($idKey)->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->unique()->values()->all();
        if (empty($ids))
            return $rows;

        $types = \Illuminate\Support\Facades\DB::table('goods')
            ->join('types', 'types.id', 'goods.type_id')
            ->whereIntegerInRaw('goods.id', $ids)
            ->pluck('types.name', 'goods.id');

        foreach ($list as $row) {
            if (! is_object($row))
                continue;
            $id = $row->{$idKey} ?? null;
            $row->{$nameKey} = self::formatName($types[(int) $id] ?? null, $row->{$nameKey} ?? '');
        }

        return $rows;
    }
    
    public function category()
    {
        return $this->belongsTo('App\Models\Category');
    }
    
    public function type()
    {
        return $this->belongsTo('App\Models\Type');
    }
    
    public function brand()
    {
        return $this->belongsTo('App\Models\Brand');
    }
    
    public function good_units()
    {
        return $this->hasMany('App\Models\GoodUnit')->orderBy('selling_price', 'asc');
    }
    
    public function good_photos()
    {
        return $this->hasMany('App\Models\GoodPhoto');
    }

    public function base_unit()
    {
        if($this->base_unit_id == null)
            return $this->getPcsSellingPrice();

        $good_unit = GoodUnit::find($this->base_unit_id);

        if($good_unit == null)
            return $this->getPcsSellingPrice();
        
        return $good_unit;
    }

    public function getType()
    {
        if($this->type_id == null)
            return '-';
        else return $this->type->name;
    }

    public function profilePicture()
    {
        return GoodPhoto::where('good_id', $this->id)
                        ->where('is_profile_picture', 1)
                        ->first();
    }

    public function good_loadings()
    {
        return GoodLoadingDetail::join('good_units', 'good_units.id', 'good_loading_details.good_unit_id')
                                ->join('good_loadings', 'good_loadings.id', 'good_loading_details.good_loading_id')
                                ->select('good_loading_details.*')
                                ->where('good_units.good_id', $this->id)
                                ->where('good_loadings.deleted_at', null)
                                // ->where('good_units.deleted_at', null)
                                ->get();
    }

    public function goodLoadingWithTrashed($pagination)
    {
        if($pagination == 'all')
            return GoodLoading::join('good_loading_details', 'good_loadings.id', 'good_loading_details.good_loading_id')
                              ->join('good_units', 'good_units.id', 'good_loading_details.good_unit_id')
                              ->select('good_loadings.*', 'good_loading_details.*', 'good_units.*', 'good_loadings.id as gid', 'good_loadings.created_at as gca', 'good_loadings.deleted_at as gda', 'good_loading_details.selling_price as selling_price')
                              ->where('good_units.good_id', $this->id)
                              ->withTrashed()
                              ->orderBy('good_loadings.id', 'desc')
                              ->get();
        else
            return GoodLoading::join('good_loading_details', 'good_loadings.id', 'good_loading_details.good_loading_id')
                              ->join('good_units', 'good_units.id', 'good_loading_details.good_unit_id')
                              ->select('good_loadings.*', 'good_loading_details.*', 'good_units.*', 'good_loadings.id as gid', 'good_loadings.created_at as gca', 'good_loadings.deleted_at as gda', 'good_loadings.role', 'good_loadings.role_id', 'good_loading_details.selling_price as selling_price')
                              ->where('good_units.good_id', $this->id)
                              ->withTrashed()
                              ->orderBy('good_loadings.id', 'desc')
                              ->paginate($pagination);

    }
    
    public function good_transactions()
    {
        return TransactionDetail::join('good_units', 'good_units.id', 'transaction_details.good_unit_id')
                                ->join('transactions', 'transactions.id', 'transaction_details.transaction_id')
                                ->where('good_units.good_id', $this->id)
                                ->where('transaction_details.type', '!=', 'retur')
                                ->where('transactions.deleted_at', null)
                                // ->where('good_units.deleted_at', null) ini kalau di uncomment, loadingan yang unitnya kehapus bakal gak kehitung
                                ->get();
    }

    public function goodTransactionWithTrashed($start_date, $end_date, $pagination)
    {
        if($pagination == 'all')
            return Transaction::join('transaction_details', 'transactions.id', 'transaction_details.transaction_id')
                              ->join('good_units', 'good_units.id', 'transaction_details.good_unit_id')
                              ->select('transactions.*', 'transaction_details.*', 'good_units.*', 'transactions.id as gid', 'transactions.created_at as gca', 'transactions.deleted_at as gda')
                              ->where('good_units.good_id', $this->id)
                              ->whereDate('transactions.created_at', '>=', $start_date)
                              ->whereDate('transactions.created_at', '<=', $end_date)
                              ->withTrashed()
                              ->orderBy('transactions.id', 'desc')
                              ->get();
        else
            return Transaction::join('transaction_details', 'transactions.id', 'transaction_details.transaction_id')
                              ->join('good_units', 'good_units.id', 'transaction_details.good_unit_id')
                              ->select('transactions.*', 'transaction_details.*', 'good_units.*', 'transactions.id as gid', 'transactions.created_at as gca', 'transactions.deleted_at as gda')
                              ->where('good_units.good_id', $this->id)
                              ->whereDate('transactions.created_at', '>=', $start_date)
                              ->whereDate('transactions.created_at', '<=', $end_date)
                              ->withTrashed()
                              ->orderBy('transactions.id', 'desc')
                              ->paginate($pagination);

    }

    public function getPcsSellingPrice()
    {
        $good_unit = GoodUnit::join('units', 'good_units.unit_id', 'units.id')
                       ->select('good_units.*', 'units.*', 'good_units.id as id')
                       ->where('units.quantity', '1')
                       ->where('good_units.good_id', $this->id)
                       ->first();

        if($good_unit == null)
        {
            $good_unit = GoodUnit::join('units', 'good_units.unit_id', 'units.id')
                       ->select('good_units.*', 'units.*', 'good_units.id as id')
                       ->where('good_units.good_id', $this->id)
                       ->orderByRaw('CONVERT(units.quantity, INT) asc')
                       ->first();
        }

        if($good_unit == null)
        {
            dd($this->id);die;
        }

        return $good_unit;
    }

    public function getLastBuy()
    {
        return GoodLoadingDetail::join('good_units', 'good_units.id', 'good_loading_details.good_unit_id')
                                ->join('good_loadings', 'good_loadings.id', 'good_loading_details.good_loading_id')
                                ->select('good_loading_details.*', 'good_loadings.loading_date as loading_date')
                                ->where('good_units.good_id', $this->id)
                                ->where('good_loadings.deleted_at', null)
                                ->where('good_units.deleted_at', null)
                                ->where('checker', '!=', 'Created by system')
                                ->where('checker', '!=', 'Load by sistem')
                                ->orderBy('good_loadings.loading_date', 'desc')
                                ->first();
    }

    public function getStock()
    {
        // Same definition as before (loadings - transactions, without retur
        // lines, in the smallest unit), now shared with goods.last_stock.
        return \App\Services\StockService::stock($this);
    }

    public function getStockWoLastLoad($good_loading_id)
    {
        [$loaded, $sold] = \App\Services\StockService::history($this->id);

        $loaded -= GoodLoadingDetail::join('good_loadings', 'good_loadings.id', 'good_loading_details.good_loading_id')
                                    ->join('good_units', 'good_units.id', 'good_loading_details.good_unit_id')
                                    ->where('good_units.good_id', $this->id)
                                    ->where('good_loadings.id', $good_loading_id)
                                    ->where('good_loadings.deleted_at', null)
                                    ->sum('real_quantity');

        return ($loaded - $sold) / \App\Services\StockService::smallestQuantity($this);
    }

    public function getDistributor()
    {
        if($this->last_distributor_id == null)
        {
            if($this->getLastBuy() == null)
                return Distributor::where('name', 'Lainnya')->first();
            else
                return $this->getLastBuy()->good_loading->distributor;
        }
        return Distributor::find($this->last_distributor_id);
    }

    public function getLastTransaction()
    {
        return TransactionDetail::join('good_units', 'good_units.id', 'transaction_details.good_unit_id')
                                ->join('transactions', 'transactions.id', 'transaction_details.transaction_id')
                                ->select('transaction_details.*')
                                ->where('good_units.good_id', $this->id)
                                ->where('transactions.deleted_at', null)
                                ->where('good_units.deleted_at', null)
                                ->where('transaction_details.type', '!=', 'retur')
                                ->orderBy('transaction_details.id', 'desc')
                                ->first();
    }
}
