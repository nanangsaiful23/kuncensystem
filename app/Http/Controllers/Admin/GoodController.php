<?php

namespace App\Http\Controllers\Admin;

use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\GoodControllerBase;

use App\Models\Good;

class GoodController extends Controller
{
    use GoodControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Good';
        $default['page'] = 'Good';
        $default['section'] = 'all';

        $goods = $this->indexGoodBase($pagination);

        return view('admin.layout.page', compact('default', 'Goods', 'pagination'));
    }

    public function searchById($good_id)
    {
        $good = $this->searchByIdGoodBase($good_id);

        return response()->json([
            "good"  => $good
        ], 200);
    }

    public function store(Request $request)
    {
        $good = $this->storeGoodBase($request);

        return response()->json([
            "good"  => $good
        ], 200);
    }

    public function detail($good_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail Good';
        $default['page'] = 'Good';
        $default['section'] = 'detail';

        $good = Good::find($good_id);

        return view('admin.layout.page', compact('default', 'Good'));
    }

    public function edit($good_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah Good';
        $default['page'] = 'Good';
        $default['section'] = 'edit';

        $good = Good::find($good_id);

        return view('admin.layout.page', compact('default', 'Good'));
    }

    public function update($good_id, Request $request)
    {
        $good = $this->updateGoodBase($good_id, $request);

        session(['alert' => 'edit', 'data' => 'Good barang']);

        return redirect('/admin/Good/' . $good->id . '/detail');
    }

    public function delete($good_id)
    {
        $this->deleteGoodBase($good_id);

        session(['alert' => 'delete', 'data' => 'Good barang']);

        return redirect('/admin/Good/all/10');
    }
}
