<?php

namespace App\Services;

use App\Models\Journal;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Year end closing (tutup buku). The balance of every income and expense
 * account (4xxx, 5xxx, 6xxx) up to 31 December is moved to Laba Ditahan with
 * journals of type 'closing' (type_id = the year). Nothing is deleted: the
 * year stays readable, the next year's profit starts from zero, and the
 * period is locked (PeriodLock).
 *
 * Closing again replaces the year's closing journals, so a year can be closed
 * again after an urgent change by a super admin.
 */
class YearClosing
{
    public static function isProfitLossCode(?string $code): bool
    {
        return in_array(substr((string) $code, 0, 1), ['4', '5', '6'], true);
    }

    /** Opening balance (accounts.balance) as debit minus credit. */
    public static function signedOpening($account): float
    {
        $value = (float) preg_replace('/[^0-9.\-]/', '', (string) $account->balance);

        // accounts.type holds the normal side (Debet / Kredit)
        return str_starts_with(strtolower(trim((string) $account->type)), 'debet') ? $value : -$value;
    }

    public static function retainedEarningsAccountId(): int
    {
        $cfg = config('kuncen.retained_earnings_account');
        $id = DB::table('accounts')->whereNull('deleted_at')->where('code', $cfg['code'])->value('id');

        return $id ?: DB::table('accounts')->insertGetId([
            'code' => $cfg['code'], 'name' => $cfg['name'], 'type' => 'Kredit', 'group' => 'Neraca',
            'activa' => 'Pasiva', 'balance' => '0', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** @return array<int, object{id:int,code:string,name:string,debit_minus_credit:float}> */
    public static function profitLossBalances(string $upTo): array
    {
        $rows = DB::select("select a.id, a.code, a.name, a.balance, a.type,
                coalesce((select sum(j.debit) from journals j where j.deleted_at is null and j.debit_account_id = a.id and j.journal_date <= ?), 0)
              - coalesce((select sum(j.credit) from journals j where j.deleted_at is null and j.credit_account_id = a.id and j.journal_date <= ?), 0) mutation
            from accounts a order by a.code", [$upTo, $upTo]);

        $result = [];
        foreach ($rows as $r) {
            if (! self::isProfitLossCode($r->code)) {
                continue;
            }
            $r->debit_minus_credit = round((float) $r->mutation + self::signedOpening($r), 2);
            if (abs($r->debit_minus_credit) >= 0.01) {
                $result[] = $r;
            }
        }

        return $result;
    }

    public static function isClosed(int $year): bool
    {
        return Journal::where('type', 'closing')->where('type_id', $year)->exists();
    }

    /** @return float profit of the closed period that went to Laba Ditahan */
    public static function close(int $year): float
    {
        if ($year >= (int) date('Y')) {
            throw new RuntimeException("Tahun $year belum selesai, belum bisa tutup buku.");
        }

        return DB::transaction(fn () => PeriodLock::bypass(function () use ($year) {
            $date = "$year-12-31";
            // closing again: replace this year's closing journals
            Journal::where('type', 'closing')->where('type_id', $year)->get()->each->delete();

            $retained = self::retainedEarningsAccountId();
            $profit = 0.0;
            foreach (self::profitLossBalances($date) as $acc) {
                $amount = abs($acc->debit_minus_credit);
                $expense = $acc->debit_minus_credit > 0; // debit balance: cost, otherwise income
                $profit += -$acc->debit_minus_credit;
                Journal::create([
                    'type' => 'closing', 'type_id' => $year, 'journal_date' => $date,
                    'name' => "Tutup buku $year: {$acc->code} {$acc->name}",
                    'debit_account_id' => $expense ? $retained : $acc->id, 'debit' => $amount,
                    'credit_account_id' => $expense ? $acc->id : $retained, 'credit' => $amount,
                ]);
            }

            return round($profit, 2);
        }));
    }

    /** Open a closed year again; later closed years are opened too. */
    public static function reopen(int $year): int
    {
        return DB::transaction(fn () => PeriodLock::bypass(function () use ($year) {
            $journals = Journal::where('type', 'closing')->where('type_id', '>=', $year)->get();
            $journals->each->delete();

            return $journals->pluck('type_id')->unique()->count();
        }));
    }
}
