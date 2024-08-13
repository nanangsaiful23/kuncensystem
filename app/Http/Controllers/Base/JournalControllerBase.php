<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\Account;
use App\Models\Journal;

trait JournalControllerBase 
{
    public function indexJournalBase($code, $type, $start_date, $end_date, $sort, $order, $pagination)
    {
      if($code != 'all')
         $account = Account::where('code', $code)->first();

        if($pagination == 'all')
        {
            if($code == 'all')
            { 
                if($type == 'all')
                {
                   $journals = Journal::whereDate('journals.created_at', '>=', $start_date)
                                      ->whereDate('journals.created_at', '<=', $end_date)
                                      ->orderBy($sort, $order)
                                      ->get(); 
                }
                else
                {
                   $journals = Journal::whereDate('journals.created_at', '>=', $start_date)
                                      ->whereDate('journals.created_at', '<=', $end_date)
                                      ->where('journals.type', $type)
                                      ->orderBy($sort, $order)
                                      ->get();
                }
            }
            else
            {
                if($type == 'all')
                {
                   $journals = Journal::whereDate('journals.created_at', '>=', $start_date)
                                      ->whereDate('journals.created_at', '<=', $end_date)
                                      ->whereRaw('(journals.debit_account_id = ' . $account->id . ' OR journals.credit_account_id = ' . $account->id . ')')
                                      ->orderBy($sort, $order)
                                      ->get(); 
                }
                else
                {
                   $journals = Journal::whereDate('journals.created_at', '>=', $start_date)
                                      ->whereDate('journals.created_at', '<=', $end_date)
                                      ->where('journals.type', $type)
                                      ->whereRaw('(journals.debit_account_id = ' . $account->id . ' OR journals.credit_account_id = ' . $account->id . ')')
                                      ->orderBy($sort, $order)
                                      ->get();
                }
            }
        }
        else
        {
            if($code == 'all')
            { 
                if($type == 'all')
                {
                   $journals = Journal::whereDate('journals.created_at', '>=', $start_date)
                                      ->whereDate('journals.created_at', '<=', $end_date)
                                      ->orderBy($sort, $order)
                                      ->paginate($pagination);
                }
                else
                {
                   $journals = Journal::whereDate('journals.created_at', '>=', $start_date)
                                      ->whereDate('journals.created_at', '<=', $end_date)
                                      ->where('journals.type', $type)
                                      ->orderBy($sort, $order)
                                      ->paginate($pagination);
                }
            }
            else
            {
                if($type == 'all')
                {
                   $journals = Journal::whereDate('journals.created_at', '>=', $start_date)
                                      ->whereDate('journals.created_at', '<=', $end_date)
                                      ->whereRaw('(journals.debit_account_id = ' . $account->id . ' OR journals.credit_account_id = ' . $account->id . ')')
                                      ->orderBy($sort, $order)
                                      ->paginate($pagination);
                }
                else
                {
                   $journals = Journal::whereDate('journals.created_at', '>=', $start_date)
                                      ->whereDate('journals.created_at', '<=', $end_date)
                                      ->where('journals.type', $type)
                                      ->whereRaw('(journals.debit_account_id = ' . $account->id . ' OR journals.credit_account_id = ' . $account->id . ')')
                                      ->orderBy($sort, $order)
                                      ->paginate($pagination);
                }
            }
        }

        return $journals;
    }

    public function storeJournalBase(Request $request)
    {
        $data = $request->input();
        $data['debit'] = unformatNumber($request->debit);
        $data['credit'] = unformatNumber($request->debit);
        $this->validate($request, [
            'debit' => array('required', 'regex:/^-?(?:\d+|\d{1,3}(?:,\d{3})+)(?:(\.|,)\d+)?$/'),
            'credit_account_id' => array('required'),
            'debit_account_id' => array('required'),
            'journal_date' => array('required'),
            // 'credit' => array('required', 'regex:/^[\d\s,]*$/'),
        ]);
        // dd($data);die;

        if(isset($data['add_on_name']))
            $data['name'] = $data['name'] . ' ' . $data['add_on_name'];
        $journal = Journal::create($data);

        return $journal;
    }

    public function updateJournalBase($journal_id, Request $request)
    {
        $data = $request->input();
        $data['debit'] = unformatNumber($request->debit);
        $data['credit'] = unformatNumber($request->debit);
        $this->validate($request, [
            'debit' => array('required', 'regex:/^-?(?:\d+|\d{1,3}(?:,\d{3})+)(?:(\.|,)\d+)?$/'),
            // 'credit' => array('required', 'regex:/^[\d\s,]*$/'),
        ]);

        $journal = Journal::find($journal_id);
        $journal->update($data);

        return $journal;
    }
}