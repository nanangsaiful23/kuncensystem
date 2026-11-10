<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Closed periods (tutup buku). A year is closed by its closing journals
 * (journals.type = 'closing', dated 31 December, see YearClosing). Up to the
 * date of the last closing journal no journal may be added, changed or
 * deleted, except by an admin whose role is in kuncen.period_unlock_roles.
 *
 * Every transaction, loading, payment and opname writes journals, so checking
 * journals (Journal model events) covers all of them; the request is then
 * rolled back as a whole (AtomicRequest).
 */
class PeriodLock
{
    private static bool $systemBypass = false;
    private static ?string $lockedUntil = null;
    private static bool $loaded = false;

    /** Last closed date (Y-m-d), or null when no year is closed. */
    public static function lockedUntil(): ?string
    {
        if (! self::$loaded) {
            self::$lockedUntil = DB::table('journals')->whereNull('deleted_at')->where('type', 'closing')->max('journal_date');
            self::$loaded = true;
        }

        return self::$lockedUntil;
    }

    public static function forget(): void
    {
        self::$loaded = false;
        self::$lockedUntil = null;
    }

    public static function isLocked(?string $date): bool
    {
        $until = self::lockedUntil();

        return $until !== null && $date !== null && substr((string) $date, 0, 10) <= $until;
    }

    /** The logged in admin may change closed periods. */
    public static function userCanUnlock(): bool
    {
        $admin = Auth::guard('admin')->user();

        return $admin !== null && in_array($admin->role, config('kuncen.period_unlock_roles', []), true);
    }

    public static function canBypass(): bool
    {
        return self::$systemBypass || self::userCanUnlock();
    }

    /** Run code that may write in closed periods (the closing itself). */
    public static function bypass(callable $fn)
    {
        $before = self::$systemBypass;
        self::$systemBypass = true;
        try {
            return $fn();
        } finally {
            self::$systemBypass = $before;
            self::forget();
        }
    }

    /** @param  array<string|null>  $dates */
    public static function assertOpen(array $dates): void
    {
        foreach ($dates as $date) {
            if (self::isLocked($date) && ! self::canBypass()) {
                throw ValidationException::withMessages([
                    'periode' => 'Periode sampai ' . displayDate(self::lockedUntil()) . ' sudah tutup buku. Data bertanggal '
                        . displayDate(substr((string) $date, 0, 10)) . ' tidak bisa ditambah, diubah atau dihapus. Hubungi super admin bila mendesak.',
                ]);
            }
        }
    }
}
