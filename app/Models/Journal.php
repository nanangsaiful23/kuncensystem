<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Account;

class Journal extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'type', 'journal_date', 'name', 'debit_account_id', 'debit', 'credit_account_id', 'credit'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];

    public function debit_account()
    {
        return Account::find($this->debit_account_id);
    }

    public function credit_account()
    {
        return Account::find($this->credit_account_id);
    }
}
