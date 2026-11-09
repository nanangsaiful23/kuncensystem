<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{    
    use \App\Models\Concerns\SerializesDatesLegacy;

    use SoftDeletes;
    
    protected $fillable = [
        'code', 'name', 'eng_name', 'unit_id', 'color'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];


    public function unit()
    {
        return $this->belongsTo('App\Models\Unit');
    }
}
