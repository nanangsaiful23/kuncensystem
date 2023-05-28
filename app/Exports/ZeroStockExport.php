<?php

namespace App\Exports;

use App\Models\Good;
use Maatwebsite\Excel\Concerns\FromArray;

class ZeroStockExport implements FromArray
{
    protected $goods;

    public function __construct(array $goods)
    {
        $this->goods = $goods;
    }

    public function array(): array
    {
        return $this->goods;
    }
}
