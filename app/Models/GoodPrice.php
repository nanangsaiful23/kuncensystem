<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Admin;
use App\Cashier;

class GoodPrice extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'role', 'role_id', 'good_unit_id', 'old_price', 'recent_price', 'reason', 'is_checked'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];

    public function actor()
    {
        if($this->role == 'admin')
            return Admin::find($this->role_id);
        else
            return Cashier::find($this->role_id);
    }
    
    public function good_unit()
    {
        return $this->belongsTo('App\Models\GoodUnit');
    }
}
