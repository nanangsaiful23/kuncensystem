<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturItem extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'good_id', 'last_distributor_id', 'returned_date', 'returned_type'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];
    
    public function good()
    {
        return $this->belongsTo('App\Models\Good');
    }
    
    public function last_distributor()
    {
        return $this->belongsTo('App\Models\Distributor');
    }
}
