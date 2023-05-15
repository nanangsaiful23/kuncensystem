<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Admin;
use App\Cashier;
use App\Models\Account;

class Transaction extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'type', 'role', 'role_id', 'member_id', 'total_item_price', 'total_discount_price', 'total_sum_price', 'money_paid', 'money_returned', 'store', 'payment'
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
    
    public function details()
    {
        return $this->hasMany('App\Models\TransactionDetail');
    }
    
    public function member()
    {
        return $this->belongsTo('App\Models\Member');
    }

    public function type_name()
    {
        return Account::where('code', $this->type)->first();
    }
}
