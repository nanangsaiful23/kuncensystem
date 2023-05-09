<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{    
    use SoftDeletes;
    
    protected $fillable = [
        'name', 'address'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $dates =[
        'deleted_at',
    ];

    public function totalCredit()
    {
        $transactions = Transaction::where('payment', 'cash')
                                    ->where('money_paid', '<=', 0)
                                    ->where('member_id', $this->id)
                                    ->get();

        return $transactions;
    }

    public function totalPayment()
    {
        $payments = PiutangPayment::where('member_id', $this->id)
                                      ->get();

        return $payments;
    }
}
