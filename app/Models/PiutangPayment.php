<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PiutangPayment extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'member_id', 'payment_date', 'money'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];
    
    public function member()
    {
        return $this->belongsTo('App\Models\Member');
    }
}
