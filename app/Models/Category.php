<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{    
    use \App\Models\Concerns\SerializesDatesLegacy;

    use SoftDeletes;
    
    protected $fillable = [
        'main_category_id', 'code', 'name', 'eng_name', 'unit_id', 'color', 'habis_pakai'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];


    public function main_category()
    {
        return $this->belongsTo('App\Models\MainCategory');
    }

    public function types()
    {
        return $this->hasMany('App\Models\Type');
    }

    public function unit()
    {
        return $this->belongsTo('App\Models\Unit');
    }
}
