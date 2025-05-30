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

    public function index($start_date, $end_date, $sort, $order, $pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Member';
        $default['page'] = 'member';
        $default['section'] = 'all';

        $members = $this->indexMemberBase($start_date, $end_date, $sort, $order, $pagination);

        return view('cashier.layout.page', compact('default', 'members', 'start_date', 'end_date', 'sort', 'order', 'pagination'));
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
}
