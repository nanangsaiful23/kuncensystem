<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Admin;
use App\Cashier;

class GoodLoading extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'role', 'role_id', 'checker', 'loading_date', 'distributor_id', 'total_item_price', 'note', 'payment'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];

    public function distributor()
    {
        return $this->belongsTo('App\Models\Distributor');
    }
    
    public function details()
    {
        return $this->hasMany('App\Models\GoodLoadingDetail');
    }

    public function actor()
    {
        if($this->role == 'admin')
            return Admin::find($this->role_id);
        else
            return Cashier::find($this->role_id);
    }
}
