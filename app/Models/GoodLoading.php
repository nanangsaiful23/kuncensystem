<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Account;
use App\Admin;
use App\Cashier;

class GoodLoading extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'role', 'role_id', 'type', 'checker', 'loading_date', 'distributor_id', 'total_item_price', 'note', 'payment'
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

    public function getDistributor()
    {
        if($this->distributor_id == null)
            return Distributor::where('name', 'Lainnya')->first();
        return Distributor::find($this->distributor_id);
    }
    
    public function details()
    {
        return $this->hasMany('App\Models\GoodLoadingDetail');
    }

    public function detailsWithDeleted()
    {
        $details = $this->details;

        foreach($details as $detail)
        {
            $detail->good_unit = GoodUnit::withTrashed()->where('id', $detail->good_unit_id)->get();
            $detail->good = Good::withTrashed()->where('id', $detail->good_unit[0]->good_id)->get();
            $detail->good_unit = $detail->good_unit[0];
            $detail->good = $detail->good[0];
        }

        return $details;
    }

    public function actor()
    {
        if($this->role == 'admin')
            return Admin::find($this->role_id);
        else
            return Cashier::find($this->role_id);
    }

    public function paymentObj()
    {
        $account = Account::where('code', $this->payment)->first();
        if($account != null)
            return $account->name;
        else
            return $this->payment;
    }   
}
