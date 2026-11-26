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
            $whereAcccount = Account::where('code', $code)->first()->id;
        else
            $whereAcccount = '%%';

        if($type == 'all')
            $whereType = '%%';
        else
            $whereType = $type;

        if($sort == 'updated_at')
            $whereDate = 'journals.updated_at';
        else
            $whereDate = 'journals.created_at';

        // Dibuat ATAU diubah di periode ini. Dalam kurung: Laravel menambahkan
        // "AND deleted_at IS NULL" di belakang; tanpa kurung jurnal yang sudah
        // dihapus (mis. tutup kasir yang dibatalkan) ikut tampil.
        $periode = '((DATE(journals.created_at) >= ? AND DATE(journals.created_at) <= ?) OR (DATE(journals.updated_at) >= ? AND DATE(journals.updated_at) <= ?))';
        $tanggal = [$start_date, $end_date, $start_date, $end_date];

        if($pagination == 'all')
        {
            if($code == 'all' && $type == 'all')
            { 
               $journals = Journal::whereRaw($periode, $tanggal)
                                  ->orderBy($sort, $order)
                                  ->get(); 
            }
            else
            {
                $journals = Journal::whereRaw($periode . " AND coalesce(journals.type, '') like ? AND (coalesce(journals.debit_account_id, '') like ? OR coalesce(journals.credit_account_id, '') like ? )", array_merge($tanggal, [$whereType, $whereAcccount, $whereAcccount]))
                                      ->orderBy($sort, $order)
                                      ->get();
            }
        }
        else
        { 
            if($code == 'all' && $type == 'all')
            { 
               $journals = Journal::whereRaw($periode, $tanggal)
                                  ->orderBy($sort, $order)
                                  ->paginate($pagination); 
            }
            else
            {
                $journals = Journal::whereRaw($periode . " AND coalesce(journals.type, '') like ? AND (coalesce(journals.debit_account_id, '') like ? OR coalesce(journals.credit_account_id, '') like ? )", array_merge($tanggal, [$whereType, $whereAcccount, $whereAcccount]))
                                      ->orderBy($sort, $order)
                                      ->paginate($pagination);
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