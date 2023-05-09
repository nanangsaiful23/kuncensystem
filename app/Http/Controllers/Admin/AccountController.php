<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Base\AccountControllerBase;

use App\Models\Account;

class AccountController extends Controller
{
    use AccountControllerBase;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index($pagination)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Daftar Akun';
        $default['page'] = 'account';
        $default['section'] = 'all';

        $accounts = $this->indexAccountBase($pagination);

        return view('admin.layout.page', compact('default', 'accounts', 'pagination'));
    }

    public function create()
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Tambah Akun';
        $default['page'] = 'account';
        $default['section'] = 'create';

        return view('admin.layout.page', compact('default'));
    }

    public function store(Request $request)
    {
        $account = $this->storeAccountBase($request);

        session(['alert' => 'add', 'data' => 'Akun']);

        return redirect('/admin/account/' . $account->id . '/detail');
    }

    public function detail($account_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Detail Akun';
        $default['page'] = 'account';
        $default['section'] = 'detail';

        $account = Account::find($account_id);

        return view('admin.layout.page', compact('default', 'account'));
    }

    public function edit($account_id)
    {
        [$default['type'], $default['color'], $default['data']] = alert();

        $default['page_name'] = 'Ubah Akun';
        $default['page'] = 'account';
        $default['section'] = 'edit';

        $account = Account::find($account_id);

        return view('admin.layout.page', compact('default', 'account'));
    }

    public function update($account_id, Request $request)
    {
        $account = $this->updateAccountBase($account_id, $request);

        session(['alert' => 'edit', 'data' => 'Akun']);

        return redirect('/admin/account/' . $account->id . '/detail');
    }

    public function delete($account_id)
    {
        $this->deleteAccountBase($account_id);

        session(['alert' => 'delete', 'data' => 'Akun']);

        return redirect('/admin/account/all/10');
    }
}
