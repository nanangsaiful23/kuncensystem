<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodPrice extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'role', 'role_id', 'good_unit_id', 'old_price', 'recent_price', 'reason'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];
}
