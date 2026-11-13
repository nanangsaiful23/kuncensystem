<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockInspectionDetail extends Model
{
    use \App\Models\Concerns\SerializesDatesLegacy;

    protected $fillable = [
        'stock_inspection_id', 'good_id', 'good_unit_id', 'unit_name',
        'system_stock', 'real_stock', 'difference', 'buy_price', 'source', 'note',
    ];

    public function stock_inspection()
    {
        return $this->belongsTo(StockInspection::class);
    }

    public function good()
    {
        return $this->belongsTo(Good::class)->withTrashed();
    }

    /** Nilai selisih dalam rupiah (harga beli x selisih), minus = barang kurang. */
    public function valueDifference(): float
    {
        return round((float) $this->difference * (float) $this->buy_price, 2);
    }
}
