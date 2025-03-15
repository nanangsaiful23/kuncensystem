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
        'type', 'role', 'role_id', 'member_id', 'total_item_price', 'total_discount_price', 'total_sum_price', 'voucher', 'voucher_nominal', 'money_paid', 'money_returned', 'store', 'payment', 'note'
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

    public function detailsWithDeleted()
    {
        $details = $this->details;

        foreach($details as $detail)
        {
            $detail->good_unit = GoodUnit::withTrashed()->where('id', $detail->good_unit_id)->first();
            $detail->good = Good::withTrashed()->where('id', $detail->good_unit->good_id)->first();
            // $detail->good_unit = $detail->good_unit[0];
            // $detail->good = $detail->good[0];
        }

        return $details;
    }
    
    public function member()
    {
        return $this->belongsTo('App\Models\Member');
    }

    public function type_name()
    {
        return Account::where('code', $this->type)->first();
    }

    public function getProfit()
    {
        $sum = 0;

        $details = $this->details;

        foreach($details as $detail)
        {
            $sum += ($detail->selling_price - $detail->buy_price) * $detail->quantity;
        }

        return $sum - checkNull($this->total_discount_price) - checkNull($this->voucher_nominal);
    }

    public function getHpp()
    {
        $sum = 0;

        $details = $this->details;

        foreach($details as $detail)
        {
            $sum += ($detail->buy_price) * $detail->quantity;
        }

        return $sum;
    }
}
