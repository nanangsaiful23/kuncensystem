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

    public function detailsWithDeleted()
    {
        $details = $this->stock_opname_details;

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
}
