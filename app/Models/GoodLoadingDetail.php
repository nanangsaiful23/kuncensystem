<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodLoadingDetail extends Model
{    
    use \App\Models\Concerns\SerializesDatesLegacy;

    use SoftDeletes;
    
    protected $fillable = [
        'good_loading_id', 'good_unit_id', 'last_stock', 'quantity', 'real_quantity', 'price', 'selling_price', 'expiry_date'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    
    protected static function booted(): void
    {
        // Keep goods.last_stock in step with the history (see StockService).
        $sync = function (self $detail) {
            \App\Services\StockService::queueGoodUnit($detail->good_unit_id);

            if ($detail->wasChanged('good_unit_id')) {
                \App\Services\StockService::queueGoodUnit($detail->getOriginal('good_unit_id'));
            }
        };

        static::saved($sync);
        static::deleted($sync);
        static::restored($sync);
    }

    public function good_loading()
    {
        return $this->belongsTo('App\Models\GoodLoading');
    }
    
    public function good_unit()
    {
        return $this->belongsTo('App\Models\GoodUnit');
    }
}
