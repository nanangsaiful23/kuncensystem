<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use File;

use App\Models\GoodPhoto;

trait GoodPhotoControllerBase 
{
    public function storeGoodPhotoBase($good_id, Request $request)
    {
        $data = $request->input();
        $data['good_id'] = $good_id;

        if($request->hasFile('file')) 
        {
            $path = $request->file('file');

            $data['server'] = 'web';
            $data['location'] = resizeImage("good_photos", $path);
        }

        $photo = GoodPhoto::create($data);

        return $photo;
    }

    public function makeProfilePictureGoodPhotoBase($good_id, $photo_id)
    {
        $data['is_profile_picture'] = 1;

        $photo = GoodPhoto::find($photo_id);
        $photo->update($data);

        return $photo;
    }

    public function deleteGoodPhotoBase($good_id, $photo_id)
    {
        $photo = GoodPhoto::find($photo_id);

        $path = Storage::disk('public')->getDriver()->getAdapter()->applyPathPrefix($photo->location);
        File::delete($path);

        $photo->delete();

        return true;
    }
}
