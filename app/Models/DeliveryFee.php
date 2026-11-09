<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryFee extends Model
{    
    use \App\Models\Concerns\SerializesDatesLegacy;

    use SoftDeletes;
    
    protected $fillable = [
        'provinsi', 'kota_kab', 'kecamatan', 'desa', 'rt_rw', 'distance', 'date_fee', 'fee', 'location'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

}
