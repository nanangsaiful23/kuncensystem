<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Transaksi dari Kasir HP yang ditolak server; disimpan supaya tidak hilang dan bisa dicek admin. */
class PosSyncReject extends Model
{
    protected $fillable = [
        'pos_device_id', 'client_uuid', 'receipt_no', 'payload', 'reason', 'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(PosDevice::class, 'pos_device_id')->withTrashed();
    }

    public function data(): array
    {
        return json_decode($this->payload, true) ?: [];
    }
}
