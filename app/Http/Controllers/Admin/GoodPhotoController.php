<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\GoodPhotoControllerBase;

use App\Models\Good;
use App\Models\GoodPhoto;

class GoodPhotoController extends Controller
{
    use GoodPhotoControllerBase;

    public function __construct()
    {
        $this->middleware('auth');
        $this->admin = \Auth::user();
    }

    public function index($good_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Foto';
        $default['page'] = 'good-photo';
        $default['section'] = 'all';

        $good = Good::find($good_id);

        return view('admin.layout.page', compact('default', 'good'));
    }

    public function create($good_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah Foto';
        $default['page'] = 'good-photo';
        $default['section'] = 'create';

        $good = Good::find($good_id);

        return view('admin.layout.page', compact('default', 'good'));
    }

    public function store($good_id, Request $request)
    {
        $photo = $this->storeGoodPhotoBase($good_id, $request);

        session(['alert' => 'add', 'data' => 'Foto barang']);

        return redirect('admin/good/' . $good_id . '/photo/10');
    }

    public function makeProfilePicture($good_id, $photo_id)
    {
        $photo = $this->makeProfilePictureGoodPhotoBase($good_id, $photo_id);

        session(['alert' => 'add', 'data' => 'Profile picture barang']);

        return redirect('admin/good/' . $good_id . '/photo/10');
    }

    public function delete($good_id, $photo_id)
    {
        $this->deleteGoodPhotoBase($good_id, $photo_id);

        session(['alert' => 'delete', 'data' => 'Foto barang']);

        return redirect('admin/good/' . $good_id . '/photo/10');
    }
}