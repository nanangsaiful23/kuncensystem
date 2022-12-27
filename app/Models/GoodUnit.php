<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodUnit extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'good_id', 'unit_id', 'buy_price', 'selling_price'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];
}
