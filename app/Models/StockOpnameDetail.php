<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockOpnameDetail extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'stock_opname_id', 'good_unit_id', 'old_stock', 'new_stock', 'total'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];
    
    public function stock_opname()
    {
        return $this->belongsTo('App\Models\StockOpname');
    }
    
    public function good_unit()
    {
        return $this->belongsTo('App\Models\GoodUnit');
    }
}
