<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

use App\Models\Good;

class ChangeNameImport implements ToCollection
{
    public function __construct() 
    {
    }

    public function collection(Collection $rows)
    {
        foreach($rows as $row)
        {
            if($row[0] != null)
            {
                $good = Good::find($row[0]);

                if($good != null)
                {
            // dd($row);die;
                    $data_good['code'] = $row[1];
                    $data_good['name'] = $row[2];
                    $data_good['type_id'] = $row[3];

                    $good->update($data_good);
                }
            }
        }
    }
}
