<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubCategory extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'main_category_id', 'category_id'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];
    
    public function main_category()
    {
        return $this->belongsTo('App\Models\MainCategory');
    }
    
    public function category()
    {
        return $this->belongsTo('App\Models\Category');
    }
}
