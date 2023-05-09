<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\Account;

trait AccountControllerBase 
{
    public function indexAccountBase($pagination)
    {
        if($pagination == 'all')
           $accounts = Account::orderBy('code', 'asc')->get();
        else
           $accounts = Account::orderBy('code', 'asc')->paginate($pagination);

        return $accounts;
    }

    public function storeAccountBase(Request $request)
    {
        $data = $request->input();

        $account = Account::create($data);

        return $account;
    }

    public function updateAccountBase($account_id, Request $request)
    {
        $data = $request->input();

        $account = Account::find($account_id);
        $account->update($data);

        return $account;
    }

    public function deleteAccountBase($account_id)
    {
        $account = Account::find($account_id);
        $account->delete();
    }
}
