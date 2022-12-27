<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'code', 'name', 'eng_name', 'unit'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];

    public function unit()
    {
        return $this->belongsTo('App\Models\Unit');
    }
}
