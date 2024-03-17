<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'code', 'name', 'start_period', 'end_period', 'quota', 'type', 'nominal', 'is_valid'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];
}
