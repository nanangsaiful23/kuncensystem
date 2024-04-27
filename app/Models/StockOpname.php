<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Admin;
use App\Cashier;

class StockOpname extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'role', 'role_id', 'checker', 'note', 'total'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];
    
    public function stock_opname_details()
    {
        return $this->hasMany('App\Models\StockOpnameDetail');
    }

    public function actor()
    {
        if($this->role == 'admin')
            return Admin::find($this->role_id);
        else
            return Cashier::find($this->role_id);
    }
}
