<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Type extends Model
{    
    use \App\Models\Concerns\SerializesDatesLegacy;

    use SoftDeletes;
    
    protected $fillable = [
        'category_id', 'code', 'name', 'eng_name', 'tipe_kontrol', 'min_model', 'max_model'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    public function category()
    {
        return $this->belongsTo('App\Models\Category');
    }
}
