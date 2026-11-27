<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Satu rentang tanggal sebuah musim (lihat App\Models\Season). */
class SeasonPeriod extends Model
{
    protected $fillable = ['season_id', 'start_date', 'end_date'];

    protected $casts = [
        'season_id'  => 'integer',
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }
}
