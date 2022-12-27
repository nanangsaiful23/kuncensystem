<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Good extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'category_id', 'code', 'name'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];
    
    public function good_units()
    {
        return $this->hasMany('App\Models\GoodUnit');
    }
}
