<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScaleLedger extends Model
{    
    use \App\Models\Concerns\SerializesDatesLegacy;

    use SoftDeletes;
    
    protected $fillable = [
        'start_date', 'end_date', 'account_id', 'initial', 'ongoing', 'current'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];


    public function account()
    {
        return $this->belongsTo('App\Models\Account');
    }
}
