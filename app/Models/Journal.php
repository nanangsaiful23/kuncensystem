<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Account;

class Journal extends Model
{    
    use \App\Models\Concerns\SerializesDatesLegacy;

    use SoftDeletes;
    
    protected $fillable = [
        'type', 'type_id', 'journal_date', 'name', 'debit_account_id', 'debit', 'credit_account_id', 'credit'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];


    private static ?bool $kolomPembuat = null;

    /** Kolom created_by_role/created_by_id sudah ada? (dibuat oleh migrasi Tutup Kasir) */
    public static function punyaKolomPembuat(): bool
    {
        return self::$kolomPembuat ??= \Illuminate\Support\Facades\Schema::hasColumn('journals', 'created_by_role');
    }

    /** Lupakan hasil cek kolom (setelah kolomnya baru dibuat). */
    public static function lupakanKolomPembuat(): void
    {
        self::$kolomPembuat = null;
    }

    protected static function booted(): void
    {
        // Catat akun yang menginput (admin/kasir yang login). Dipakai panduan per
        // laci di Tutup Kasir. API Kasir HP tidak punya login web: dibiarkan kosong.
        static::creating(function (Journal $journal) {
            if ($journal->created_by_role !== null || ! \Auth::hasUser() || ! self::punyaKolomPembuat()) {
                return;
            }
            $user = \Auth::user();
            if ($user instanceof \App\Cashier || $user instanceof \App\Admin) {
                $journal->created_by_role = $user instanceof \App\Cashier ? 'cashier' : 'admin';
                $journal->created_by_id   = $user->id;
            }
        });

        // Closed periods (tutup buku) may not change; see PeriodLock.
        static::saving(function (Journal $journal) {
            \App\Services\PeriodLock::assertOpen([$journal->journal_date, $journal->exists ? $journal->getOriginal('journal_date') : null]);
        });
        static::deleting(fn (Journal $journal) => \App\Services\PeriodLock::assertOpen([$journal->getOriginal('journal_date')]));
        static::restoring(fn (Journal $journal) => \App\Services\PeriodLock::assertOpen([$journal->journal_date]));
    }

    public function debit_account()
    {
        return Account::find($this->debit_account_id);
    }

    public function credit_account()
    {
        return Account::find($this->credit_account_id);
    }
}
