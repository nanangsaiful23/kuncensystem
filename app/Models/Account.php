<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{    
    use \App\Models\Concerns\SerializesDatesLegacy;

    use SoftDeletes;
    
    protected $fillable = [
        'code', 'name', 'type', 'group', 'activa', 'balance', 'color'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

}
