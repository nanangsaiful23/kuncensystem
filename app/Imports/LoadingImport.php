<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

use App\Models\Brand;
use App\Models\Distributor;
use App\Models\Good;
use App\Models\GoodLoadingDetail;
use App\Models\GoodPhoto;
use App\Models\GoodPrice;
use App\Models\GoodUnit;
use App\Models\Unit;

class LoadingImport implements ToCollection
{
    private $role, $role_id, $good_loading_id;

    public function __construct($role, $role_id, $good_loading_id) 
    {
        $this->role            = $role;
        $this->role_id         = $role_id;
        $this->good_loading_id = $good_loading_id;
    }

    public function collection(Collection $rows)
    {
        foreach($rows as $row)
        {
            if($row[0] != null)
            {
                #cek brand
                $brand = Brand::where('name', $row[3])->first();

                if($brand == null)
                {
                    $data_brand['name'] = $row[3];
                    $brand = Brand::create($data_brand);
                }

                #cek distributor
                $distributor = Distributor::where('name', $row[7])->first();

                if($distributor == null)
                {
                    $data_distributor['name'] = $row[7];

                    $distributor = Distributor::create($data_distributor);
                }

                $good = Good::where('name', $row[2])->first();

                if($good == null)
                {
                    #create good
                    $good = Good::create([
                        'category_id' => $row[0],
                        'code'        => $row[1],
                        'name'        => $row[2],
                        'brand_id'    => $brand->id,
                        'last_distributor_id' => $distributor->id
                    ]);

                    if($row[1] == null)
                    {
                        $data_good['code'] = $good->id;
                        $good->update($data_good);
                    }
                }

                $unit = Unit::where('code', $row[4])->first();
                
                $good_unit = GoodUnit::where('good_id', $good->id)
                                     ->where('unit_id', $unit->id)
                                     ->first();

                if($good_unit)
                {
                    if($good_unit->selling_price != $row[6])
                    {
                        $data_price['role']         = $this->role;
                        $data_price['role_id']      = $this->role_id;
                        $data_price['good_unit_id'] = $good_unit->id;
                        $data_price['old_price']    = $good_unit->selling_price;
                        $data_price['recent_price'] = $row[6];
                        $data_price['reason']       = 'Diubah saat loading';

                        GoodPrice::create($data_price);
                    }

                    $data_unit['buy_price']     = $row[5];
                    $data_unit['selling_price'] = $row[6];

                    $good_unit->update($data_unit);
                }
                else
                {
                    $data_unit['good_id']       = $good->id;
                    $data_unit['unit_id']       = $unit->id;
                    $data_unit['buy_price']     = $row[5];
                    $data_unit['selling_price'] = $row[6];

                    $good_unit = GoodUnit::create($data_unit);

                    $data_price['role']         = $this->role;
                    $data_price['role_id']      = $this->role_id;
                    $data_price['good_unit_id'] = $good_unit->id;
                    $data_price['old_price']    = $good_unit->selling_price;
                    $data_price['recent_price'] = $row[6];
                    $data_price['reason']       = 'Harga pertama';
                    $data_price['is_checked']   = 1;

                    GoodPrice::create($data_price);
                }

                $data_detail['good_loading_id'] = $this->good_loading_id;
                $data_detail['good_unit_id']    = $good_unit->id;
                $data_detail['last_stock']      = '0';
                $data_detail['quantity']        = $row[10];
                $data_detail['real_quantity']   = $row[10] * $good_unit->unit->quantity;
                $data_detail['price']           = $row[5];
                $data_detail['selling_price']   = $row[6];
                $data_detail['expiry_date']     = null;

                GoodLoadingDetail::create($data_detail);

                if(isset($row[11]))
                {
                    if($row[11] != null)
                    {
                        $data_photo['good_id'] = $good->id;
                        $data_photo['serve']   = 'web';
                        $data_photo['location'] = $row[11];
                        $data_photo['is_profile_picture'] = 1;

                        GoodPhoto::create($data_photo);
                    }
                }
            }
        }
    }
}
