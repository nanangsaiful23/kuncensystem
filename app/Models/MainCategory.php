<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MainCategory extends Model
{    
    use \App\Models\Concerns\SerializesDatesLegacy;

    use SoftDeletes;
    
    protected $fillable = [
        'code', 'name', 'eng_name', 'description'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

}
