<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoginHistory extends Model
{    
    use \App\Models\Concerns\SerializesDatesLegacy;

    use SoftDeletes;
    
    protected $fillable = [
        'role', 'role_id', 'device_type', 'ip_address', 
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];


    public function actor()
    {
        if($this->role == 'admin')
            return Admin::find($this->role_id);
        else
            return Cashier::find($this->role_id);
    }
}
