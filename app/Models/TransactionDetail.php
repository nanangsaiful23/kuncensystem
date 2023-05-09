<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionDetail extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'transaction_id', 'good_id', 'quantity', 'buy_price', 'selling_price', 'discount_price', 'sum_price'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];
    
    public function good()
    {
        return $this->belongsTo('App\Models\Good');
    }
}
