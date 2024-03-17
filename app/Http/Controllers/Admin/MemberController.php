<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\MemberControllerBase;

use App\Models\Member;

class MemberController extends Controller
{
    use MemberControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($start_date, $end_date, $sort, $order, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Member';
        $default['page'] = 'member';
        $default['section'] = 'all';

        $members = $this->indexMemberBase($start_date, $end_date, $sort, $order, $pagination);

        return view('admin.layout.page', compact('default', 'members', 'start_date', 'end_date', 'sort', 'order', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah Member';
        $default['page'] = 'member';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $member = $this->storeMemberBase($request);

        session(['alert' => 'add', 'data' => 'member']);

        return redirect('/admin/member/' . $member->id . '/detail');
    }

    public function detail($member_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail Member';
        $default['page'] = 'member';
        $default['section'] = 'detail';

        $member = Member::find($member_id);

        return view('admin.layout.page', compact('default', 'member'));
    }

    public function transaction($member_id, $start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Riwayat Transaksi Member';
        $default['page'] = 'member';
        $default['section'] = 'transaction';

        $member = Member::find($member_id);
        $transactions = $this->transactionMemberBase($member_id, $start_date, $end_date, $pagination);

        return view('admin.layout.page', compact('default', 'member', 'transactions', 'start_date', 'end_date', 'pagination'));
    }

    public function payment($member_id, $start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Riwayat Pembayaran Member';
        $default['page'] = 'member';
        $default['section'] = 'payment';

        $member = Member::find($member_id);
        $payments = $this->paymentMemberBase($member_id, $start_date, $end_date, $pagination);

        return view('admin.layout.page', compact('default', 'member', 'payments', 'start_date', 'end_date', 'pagination'));
    }

    public function edit($member_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah Member';
        $default['page'] = 'member';
        $default['section'] = 'edit';

        $member = Member::find($member_id);

        return view('admin.layout.page', compact('default', 'member'));
    }

    public function update($member_id, Request $request)
    {
        $member = $this->updateMemberBase($member_id, $request);

        session(['alert' => 'edit', 'data' => 'member barang']);

        return redirect('/admin/member/' . $member->id . '/detail');
    }

    public function delete($member_id)
    {
        $this->deleteMemberBase($member_id);

        session(['alert' => 'delete', 'data' => 'member']);

        return redirect('/admin/member/all/10');
    }

    public function search($member_id)
    {
        $member = Member::find($member_id);

        return response()->json([
            "member"  => $member
        ], 200);
    }

    public function searchByName($name)
    {
        $members = $this->searchByNameMemberBase($name);

        return response()->json([
            "members"  => $members
        ], 200);
    }

    public function showQrCode($member_id)
    {
        $member = Member::find($member_id);

        return view('layout.member.qr-code', compact('member'));
    }

    public function downloadQrCode($member_id)
    {
//         $member = Member::find($member_id);
// $pdf = \PDF::loadView('layout.member.qr-code');
// return $pdf->download('member.pdf');
//         $snappy = \App::make('snappy.pdf');

//         $snappy->generateFromHtml($html, '/tmp/member.pdf');
//         $file= '/tmp/member.pdf';

//         $headers = array(
//           'Content-Type: application/pdf',
//         );

//         return \Response::download($file, 'filename.pdf', $headers)->deleteFileAfterSend(true);
    }
}
