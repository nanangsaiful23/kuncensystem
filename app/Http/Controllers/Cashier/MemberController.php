<?php

namespace App\Http\Controllers\Cashier;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\MemberControllerBase;

use App\Models\Member;

class MemberController extends Controller
{
    use MemberControllerBase;

    public function __construct()
    {
        $this->middleware('cashier');
    }

    public function index($pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Member';
        $default['page'] = 'member';
        $default['section'] = 'all';

        $members = $this->indexMemberBase($pagination);

        return view('cashier.layout.page', compact('default', 'members', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah Member';
        $default['page'] = 'member';
        $default['section'] = 'create';

        return view('cashier.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $member = $this->storeMemberBase($request);

        session(['alert' => 'add', 'data' => 'member']);

        return redirect('/cashier/member/' . $member->id . '/detail');
    }

    public function detail($member_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail Member';
        $default['page'] = 'member';
        $default['section'] = 'detail';

        $member = Member::find($member_id);

        return view('cashier.layout.page', compact('default', 'member'));
    }

    public function transaction($member_id, $start_date, $end_date, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Transaksi Member';
        $default['page'] = 'member';
        $default['section'] = 'transaction';

        $member = Member::find($member_id);
        $transactions = $this->transactionMemberBase($member_id, $type, $pagination);

        return view('cashier.layout.page', compact('default', 'member', 'transactions', 'start_date', 'end_date', 'pagination'));
    }

    public function edit($member_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah Member';
        $default['page'] = 'member';
        $default['section'] = 'edit';

        $member = Member::find($member_id);

        return view('cashier.layout.page', compact('default', 'member'));
    }

    public function update($member_id, Request $request)
    {
        $member = $this->updateMemberBase($member_id, $request);

        session(['alert' => 'edit', 'data' => 'member barang']);

        return redirect('/cashier/member/' . $member->id . '/detail');
    }

    public function delete($member_id)
    {
        $this->deleteMemberBase($member_id);

        session(['alert' => 'delete', 'data' => 'member']);

        return redirect('/cashier/member/all/10');
    }
}
