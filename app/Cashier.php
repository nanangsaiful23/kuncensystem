<?php

namespace App;

use App\Notifications\CashierResetPassword;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Cashier extends Authenticatable
{
    use \App\Models\Concerns\SerializesDatesLegacy;

    use Notifiable;

    /**
     * Tambahan rahasia di belakang password sebelum di-hash/dicek (sama untuk login web kasir dan
     * login Kasir HP/PC). Password di tabel cashiers = bcrypt(password . PASSWORD_SUFFIX).
     */
    public const PASSWORD_SUFFIX = 'k&4z~1e1R*';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'is_active', 'color'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CashierResetPassword($token));
    }
}
