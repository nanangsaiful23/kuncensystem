<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodLoading extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'role', 'role_id', 'checker', 'loading_date', 'distributor_id', 'total_price', 'note'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];

    public function admin()
    {
        return $this->belongsTo('App\Admin');
    }

    public function cashier()
    {
        return $this->belongsTo('App\Cashier');
    }

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
        if($this->admin_id == null)
            return $this->cashier();
        else return $this->admin();
    }
}
