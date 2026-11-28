<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Account;
use App\Admin;
use App\Cashier;

class GoodLoading extends Model
{    
    use \App\Models\Concerns\SerializesDatesLegacy;

    use SoftDeletes;
    
    protected $fillable = [
        'role', 'role_id', 'type', 'checker', 'loading_date', 'distributor_id', 'total_item_price', 'note', 'payment',
        // Laci Tutup Kasir asal uangnya kalau dibayar dari 1111 Kas di Tangan ("cashier:15"), lihat TutupKasir.
        'cash_drawer',
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    private static ?bool $kolomLaci = null;

    /** Kolom cash_drawer sudah ada? (migrasi 2026_11_28_000002) */
    public static function punyaKolomLaci(): bool
    {
        return self::$kolomLaci ??= \Illuminate\Support\Facades\Schema::hasColumn('good_loadings', 'cash_drawer');
    }

    /**
     * Buat kolom cash_drawer kalau belum ada. Hanya dari request GET: ALTER
     * TABLE di MySQL menutup transaksi yang dibuka AtomicRequest untuk POST.
     */
    public static function pastikanKolomLaci(): bool
    {
        if (self::punyaKolomLaci()) {
            return true;
        }
        try {
            (require database_path('migrations/2026_11_28_000002_add_cash_drawer_to_good_loadings.php'))->up();
        } catch (\Throwable $e) {
            report($e);
        }
        self::$kolomLaci = null;

        return self::punyaKolomLaci();
    }

    /** Lupakan hasil cek kolom (tes yang membuat kolomnya sendiri). */
    public static function lupakanKolomLaci(): void
    {
        self::$kolomLaci = null;
    }


    public function distributor()
    {
        return $this->belongsTo('App\Models\Distributor');
    }

    public function getDistributor()
    {
        if($this->distributor_id == null)
            return Distributor::where('name', 'Lainnya')->first();
        return Distributor::find($this->distributor_id);
    }
    
    protected static function booted(): void
    {
        // Deleting or restoring the whole document changes the stock of
        // every good in it (see StockService).
        $sync = function (self $model, string $aksi) {
            foreach ($model->details()->withTrashed()->pluck('good_unit_id')->unique() as $goodUnitId) {
                \App\Services\StockService::queueGoodUnit($goodUnitId, ['aksi' => $aksi, 'good_loading_id' => (int) $model->id]);
            }
        };

        static::deleted(fn (self $model) => $sync($model, 'hapus_loading'));
        static::restored(fn (self $model) => $sync($model, 'loading'));
    }

    public function details()
    {
        return $this->hasMany('App\Models\GoodLoadingDetail');
    }

    public function detailsWithDeleted()
    {
        $details = $this->details;

        foreach($details as $detail)
        {
            $detail->good_unit = GoodUnit::withTrashed()->where('id', $detail->good_unit_id)->get();
            $detail->good = Good::withTrashed()->where('id', $detail->good_unit[0]->good_id)->get();
            $detail->good_unit = $detail->good_unit[0];
            $detail->good = $detail->good[0];
        }

        return $details;
    }

    public function actor()
    {
        if($this->role == 'admin')
            return Admin::find($this->role_id);
        else
            return Cashier::find($this->role_id);
    }

    public function paymentObj()
    {
        $account = Account::where('code', $this->payment)->first();
        if($account != null)
            return $account->name;
        else
            return $this->payment;
    }   

    public function getPayment()
    {
        return Account::where('code', $this->payment)->first();
    }
}
