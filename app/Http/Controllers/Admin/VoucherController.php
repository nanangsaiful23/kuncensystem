<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\VoucherControllerBase;

use App\Models\Voucher;

class VoucherController extends Controller
{
    use VoucherControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Voucher';
        $default['page'] = 'Voucher';
        $default['section'] = 'all';

        $vouchers = $this->indexVoucherBase($pagination);

        return view('admin.layout.page', compact('default', 'vouchers', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah Voucher';
        $default['page'] = 'Voucher';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $voucher = $this->storeVoucherBase($request);

        session(['alert' => 'add', 'data' => 'Voucher barang']);

        return redirect('/admin/voucher/' . $voucher->id . '/detail');
    }

    public function searchByCode($code)
    {
        [$voucher, $message] = $this->searchByCodeVoucherBase($code);

        return response()->json([
            "voucher"  => $voucher,
            "message"  => $message
        ], 200);
    }

    public function detail($voucher_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail Voucher';
        $default['page'] = 'Voucher';
        $default['section'] = 'detail';

        $voucher = Voucher::find($voucher_id);

        return view('admin.layout.page', compact('default', 'voucher'));
    }

    public function edit($voucher_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah Voucher';
        $default['page'] = 'Voucher';
        $default['section'] = 'edit';

        $voucher = Voucher::find($voucher_id);

        return view('admin.layout.page', compact('default', 'voucher'));
    }

    public function update($voucher_id, Request $request)
    {
        $voucher = $this->updateVoucherBase($voucher_id, $request);

        session(['alert' => 'edit', 'data' => 'Voucher barang']);

        return redirect('/admin/voucher/' . $voucher->id . '/detail');
    }

    public function delete($voucher_id)
    {
        $this->deleteVoucherBase($voucher_id);

        session(['alert' => 'delete', 'data' => 'Voucher barang']);

        return redirect('/admin/voucher/all/10');
    }
}
