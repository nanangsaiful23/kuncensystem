<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DistributorLedger extends Model
{    
    use \App\Models\Concerns\SerializesDatesLegacy;

    use SoftDeletes;
    
    protected $fillable = [
        'distributor_id', 'name', 'nominal'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];


    public function distributor()
    {
        return $this->belongsTo('App\Models\Distributor');
    }
}
