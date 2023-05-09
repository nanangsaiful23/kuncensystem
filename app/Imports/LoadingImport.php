<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

use App\Models\GoodLoading;

class LoadingImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        foreach($rows as $row)
        {

        }
    }
}
