<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{    
    use \App\Models\Concerns\SerializesDatesLegacy;

    use SoftDeletes;
    
    protected $fillable = [
        'name',
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    
    public function goods()
    {
        return $this->hasMany('App\Models\Good');
    }
}
