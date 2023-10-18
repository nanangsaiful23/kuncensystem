<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\Account;
use App\Models\Journal;

trait JournalControllerBase 
{
    public function indexJournalBase($code, $start_date, $end_date, $pagination)
    {
      if($code != 'all')
         $account = Account::where('code', $code)->first();

        if($pagination == 'all')
        {
            if($code == 'all')
            { 
               $journals = Journal::whereDate('journals.created_at', '>=', $start_date)
                                  ->whereDate('journals.created_at', '<=', $end_date)
                                  ->orderBy('journals.journal_date', 'asc')
                                  ->get();
            }
            else
            {
               $journals = Journal::whereDate('journals.created_at', '>=', $start_date)
                                  ->whereDate('journals.created_at', '<=', $end_date)
                                  ->whereRaw('(journals.debit_account_id = ' . $account->id . ' OR journals.credit_account_id = ' . $account->id . ')')
                                  ->orderBy('journals.journal_date', 'asc')
                                  ->get();
            }
        }
        else
        {
            if($code == 'all')
            { 
               $journals = Journal::whereDate('journals.created_at', '>=', $start_date)
                                  ->whereDate('journals.created_at', '<=', $end_date)
                                  ->orderBy('journals.journal_date', 'asc')
                                  ->paginate($pagination);
            }
            else
            {
               $journals = Journal::whereDate('journals.created_at', '>=', $start_date)
                                  ->whereDate('journals.created_at', '<=', $end_date)
                                  ->whereRaw('(journals.debit_account_id = ' . $account->id . ' OR journals.credit_account_id = ' . $account->id . ')')
                                  ->orderBy('journals.journal_date', 'asc')
                                  ->paginate($pagination);
            }
        }

        return $journals;
    }

    public function storeJournalBase(Request $request)
    {
        $data = $request->input();
        $data['debit'] = unformatNumber($request->debit);
        $data['credit'] = unformatNumber($request->credit);
        $this->validate($request, [
            'debit' => array('required', 'regex:/^[\d\s,]*$/'),
            'credit' => array('required', 'regex:/^[\d\s,]*$/'),
        ]);
        // dd($data);die;

        $journal = Journal::create($data);

        return $journal;
    }
}