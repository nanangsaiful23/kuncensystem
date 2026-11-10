<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodPhoto extends Model
{    
    use \App\Models\Concerns\SerializesDatesLegacy;

    use SoftDeletes;
    
    protected $fillable = [
        'good_id', 'server', 'location', 'is_profile_picture'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

}
