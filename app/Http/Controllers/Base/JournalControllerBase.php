<?php

namespace App\Http\Controllers\Base;

use Illuminate\Http\Request;

use App\Models\Journal;

trait JournalControllerBase 
{
    public function indexJournalBase($start_date, $end_date, $pagination)
    {
        if($pagination == 'all')
           $journals = Journal::whereDate('journals.created_at', '>=', $start_date)
                               ->whereDate('journals.created_at', '<=', $end_date)
                               ->orderBy('journals.journal_date', 'asc')
                               ->get();
        else
           $journals = Journal::whereDate('journals.created_at', '>=', $start_date)
                               ->whereDate('journals.created_at', '<=', $end_date)
                               ->orderBy('journals.journal_date', 'asc')
                               ->paginate($pagination);

        return $journals;
    }
}