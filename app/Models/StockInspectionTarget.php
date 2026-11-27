<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu barang di daftar tugas cek stok sebuah sesi sidak. position = nomor
 * urut, sama dengan nomor di teks WA untuk pegawai yang menghitung.
 */
class StockInspectionTarget extends Model
{
    use \App\Models\Concerns\SerializesDatesLegacy;

    protected $fillable = ['stock_inspection_id', 'good_id', 'position'];

    public function good()
    {
        return $this->belongsTo(Good::class)->withTrashed();
    }
}
