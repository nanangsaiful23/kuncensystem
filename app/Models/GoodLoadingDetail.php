<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodLoadingDetail extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'good_loading_id', 'good_unit_id', 'last_stock', 'quantity', 'real_quantity', 'price', 'selling_price', 'expiry_date'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];
    
    public function good_loading()
    {
        return $this->belongsTo('App\Models\GoodLoading');
    }
    
    public function good_unit()
    {
        return $this->belongsTo('App\Models\GoodUnit');
    }
}
