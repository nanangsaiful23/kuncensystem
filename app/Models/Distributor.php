<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Distributor extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'name', 'location'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];

    public function totalHutangDagang()
    {
        $journals = Journal::where('type', 'hutang dagang ' . $this->id)
                           ->get();

        return $journals;
    }

    public function totalPiutangDagang()
    {
        $journals = Journal::where('type', 'piutang dagang ' . $this->id)
                           ->get();

        return $journals;
    }
}
