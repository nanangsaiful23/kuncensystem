<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Admin;
use App\Cashier;

/**
 * Satu sesi sidak stok toko. Hanya catatan pemeriksaan: tidak mengubah stok.
 */
class StockInspection extends Model
{
    use \App\Models\Concerns\SerializesDatesLegacy;

    use SoftDeletes;

    protected $fillable = [
        'role', 'role_id', 'note', 'finished_at',
    ];

    protected $casts = [
        'finished_at' => 'datetime',
    ];

    public function details()
    {
        return $this->hasMany(StockInspectionDetail::class)->orderBy('updated_at', 'desc');
    }

    public function actor()
    {
        return $this->role == 'admin' ? Admin::find($this->role_id) : Cashier::find($this->role_id);
    }

    public function isFinished(): bool
    {
        return $this->finished_at !== null;
    }

    /** Ringkasan sesi: jumlah barang dicek, yang selisih, dan nilai selisihnya. */
    public function summary(): array
    {
        $details = $this->details;

        $minus = $details->filter(fn ($d) => (float) $d->difference < 0);
        $plus  = $details->filter(fn ($d) => (float) $d->difference > 0);

        return [
            'total'       => $details->count(),
            'match'       => $details->count() - $minus->count() - $plus->count(),
            'minus'       => $minus->count(),
            'plus'        => $plus->count(),
            'minus_value' => $minus->sum(fn ($d) => $d->valueDifference()),
            'plus_value'  => $plus->sum(fn ($d) => $d->valueDifference()),
        ];
    }
}
