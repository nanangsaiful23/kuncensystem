<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\Journal;

trait JournalControllerBase 
{
    public function indexJournalBase($start_date, $end_date, $pagination)
    {
        if($pagination == 'all')
           $journals = Journal::whereDate('journals.journal_date', '>=', $start_date)
                               ->whereDate('journals.journal_date', '<=', $end_date)
                               ->get();
        else
           $journals = Journal::whereDate('journals.journal_date', '>=', $start_date)
                               ->whereDate('journals.journal_date', '<=', $end_date)
                               ->paginate($pagination);

        return $journals;
    }
}