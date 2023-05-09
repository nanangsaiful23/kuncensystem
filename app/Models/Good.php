<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\GoodLoadingDetail;
use App\Models\GoodUnit;

class Good extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'category_id', 'code', 'name'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];
    
    public function good_units()
    {
        return $this->hasMany('App\Models\GoodUnit');
    }

    public function good_loadings()
    {
        return GoodLoadingDetail::join('good_units', 'good_units.id', 'good_loading_details.good_unit_id')
                                ->where('good_units.good_id', $this->id)
                                ->get();
    }
    
    public function good_transactions()
    {
        return $this->hasMany('App\Models\TransactionDetail');
    }

    public function getPcsSellingPrice()
    {
        return GoodUnit::join('units', 'good_units.unit_id', 'units.id')
                       ->where('units.quantity', '1')
                       ->where('good_units.good_id', $this->id)
                       ->first();
    }

    public function getLastBuy()
    {
        return GoodLoadingDetail::join('good_units', 'good_units.id', 'good_loading_details.good_unit_id')
                                ->where('good_units.good_id', $this->id)
                                ->orderBy('good_loading_details.id', 'desc')
                                ->first();
    }

    public function getStock()
    {
        $loadings = $this->good_loadings()->sum('real_quantity');

        $transactions = $this->good_transactions->sum('quantity');

        return $loadings - $transactions;
    }
}
