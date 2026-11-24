<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CashClosing;
use App\Models\Journal;
use App\Models\PosDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Tutup Kasir harian (docs/rencana-optimasi-dan-tutup-kasir.md poin 4).
 *
 * 1. Uang fisik di laci dibandingkan dengan saldo Kas di Tangan (1111).
 * 2. Selisihnya langsung dijurnal supaya saldo sistem = uang fisik:
 *    kurang -> debit 5219 Biaya Uang Hilang, kredit 1111;
 *    lebih  -> debit 1111, kredit 6101 Pendapatan Lain-lain.
 * 3. Uang yang diserahkan ke owner dijurnal seperti Penarikan Uang lama
 *    (cash_draw: debit 1113 Kas di Nanang, kredit 1111).
 *
 * Satu tanggal hanya punya satu tutup kasir yang aktif. Hanya tutup kasir
 * hari ini yang bisa dibatalkan (jurnalnya ikut dihapus), lalu ditutup ulang.
 */
class TutupKasir
{
    public const KAS_DI_TANGAN = '1111';
    public const KAS_DI_NANANG = '1113';
    public const UANG_HILANG   = '5219';
    public const PENDAPATAN_LAIN = '6101';

    /** Perangkat Kasir HP/PC yang tidak terhubung selama ini (menit) diberi peringatan. */
    public const DEVICE_STALE_MINUTES = 5;

    /** Saldo Kas di Tangan menurut sistem: saldo awal akun + debit - kredit semua jurnal. */
    public static function saldoKasDiTangan(): float
    {
        $account = self::account(self::KAS_DI_TANGAN);
        if ($account === null) {
            return 0.0;
        }

        $debit  = (float) Journal::where('debit_account_id', $account->id)->sum('debit');
        $credit = (float) Journal::where('credit_account_id', $account->id)->sum('credit');

        return round((float) $account->balance + $debit - $credit, 2);
    }

    public static function today(): ?CashClosing
    {
        return CashClosing::whereDate('closing_date', date('Y-m-d'))->latest('id')->first();
    }

    /**
     * Perangkat Kasir HP/PC aktif dengan status koneksinya. Aplikasi mengirim
     * transaksi yang tertunda begitu terhubung, jadi perangkat yang baru saja
     * terhubung dianggap sudah terkirim semua.
     *
     * @return array<int, array{name:string, code:string, last_seen_at:?\Carbon\Carbon, last_sync_at:?\Carbon\Carbon, today_count:int, stale:bool}>
     */
    public static function devices(): array
    {
        if (! PosDevice::tabelAda()) {
            return [];
        }

        $todayCounts = DB::table('transactions')
            ->whereNotNull('pos_device_id')
            ->whereNull('deleted_at')
            ->whereDate('created_at', date('Y-m-d'))
            ->groupBy('pos_device_id')
            ->selectRaw('pos_device_id, COUNT(*) AS jumlah')
            ->pluck('jumlah', 'pos_device_id');

        $limit = now()->subMinutes(self::DEVICE_STALE_MINUTES);

        return PosDevice::where('is_active', true)->orderBy('code')->get()
            ->map(fn (PosDevice $d) => [
                'name'         => $d->name,
                'code'         => $d->code,
                'last_seen_at' => $d->last_seen_at,
                'last_sync_at' => $d->last_sync_at,
                'today_count'  => (int) ($todayCounts[$d->id] ?? 0),
                'stale'        => $d->last_seen_at === null || $d->last_seen_at->lt($limit),
            ])->all();
    }

    /**
     * Simpan tutup kasir hari ini.
     *
     * @param  float|null  $saldoDilihat  saldo sistem yang tampil saat form dibuka;
     *                                     kalau sudah berubah (ada transaksi baru), ditolak.
     */
    public static function close(float $uangFisik, float $diserahkan, ?string $note, string $role, int $roleId, ?float $saldoDilihat = null): CashClosing
    {
        $date = date('Y-m-d');

        if ($uangFisik < 0) {
            throw ValidationException::withMessages(['physical_cash' => 'Uang fisik tidak boleh minus.']);
        }
        if ($diserahkan < 0) {
            throw ValidationException::withMessages(['draw_amount' => 'Jumlah yang diserahkan tidak boleh minus.']);
        }
        if ($diserahkan > $uangFisik) {
            throw ValidationException::withMessages(['draw_amount' => 'Jumlah yang diserahkan ke owner melebihi uang fisik di laci.']);
        }

        return DB::transaction(function () use ($date, $uangFisik, $diserahkan, $note, $role, $roleId, $saldoDilihat) {
            $kas = self::requireAccount(self::KAS_DI_TANGAN, 'Kas di Tangan');

            // Kunci baris akun Kas di Tangan: dua tutup kasir bersamaan menunggu
            // satu sama lain, jadi yang kedua melihat yang pertama sudah tersimpan.
            Account::whereKey($kas->id)->lockForUpdate()->first();

            if (CashClosing::whereDate('closing_date', $date)->exists()) {
                throw ValidationException::withMessages(['physical_cash' => 'Kasir hari ini sudah ditutup.']);
            }

            $saldo = self::saldoKasDiTangan();
            if ($saldoDilihat !== null && abs($saldo - $saldoDilihat) >= 0.01) {
                throw ValidationException::withMessages(['physical_cash' =>
                    'Saldo Kas di Tangan berubah dari ' . showRupiah($saldoDilihat) . ' menjadi ' . showRupiah($saldo)
                    . ' karena ada transaksi baru sejak halaman dibuka. Cek lagi angkanya lalu simpan ulang.']);
            }

            $selisih = round($uangFisik - $saldo, 2);

            $closing = CashClosing::create([
                'closing_date'   => $date,
                'system_balance' => $saldo,
                'physical_cash'  => round($uangFisik, 2),
                'difference'     => $selisih,
                'draw_amount'    => round($diserahkan, 2),
                'note'           => $note !== null && trim($note) !== '' ? trim($note) : null,
                'role'           => $role,
                'role_id'        => $roleId,
            ]);

            if ($selisih != 0) {
                if ($selisih < 0) {
                    $debit  = self::requireAccount(self::UANG_HILANG, 'Biaya Uang Hilang');
                    $credit = $kas;
                    $name   = 'Selisih kas kurang tanggal ' . $date;
                } else {
                    $debit  = $kas;
                    $credit = self::requireAccount(self::PENDAPATAN_LAIN, 'Pendapatan Lain-lain');
                    $name   = 'Selisih kas lebih tanggal ' . $date;
                }
                $closing->adjustment_journal_id = Journal::create([
                    'type' => 'cash_adjustment', 'type_id' => $closing->id, 'journal_date' => $date, 'name' => $name,
                    'debit_account_id' => $debit->id, 'debit' => abs($selisih),
                    'credit_account_id' => $credit->id, 'credit' => abs($selisih),
                ])->id;
            }

            if ($diserahkan > 0) {
                $nanang = self::requireAccount(self::KAS_DI_NANANG, 'Kas di Nanang');
                // Nama sama dengan Penarikan Uang lama, supaya tampil di Riwayat Kas di Tangan.
                $closing->draw_journal_id = Journal::create([
                    'type' => 'cash_draw', 'type_id' => $closing->id, 'journal_date' => $date,
                    'name' => 'Pengambilan uang tanggal ' . $date,
                    'debit_account_id' => $nanang->id, 'debit' => round($diserahkan, 2),
                    'credit_account_id' => $kas->id, 'credit' => round($diserahkan, 2),
                ])->id;
            }

            $closing->save();

            return $closing;
        });
    }

    /** Batalkan tutup kasir hari ini: hapus jurnalnya, lalu kasir bisa ditutup ulang. */
    public static function cancel(CashClosing $closing): void
    {
        if ($closing->closing_date->format('Y-m-d') !== date('Y-m-d')) {
            throw ValidationException::withMessages(['cancel' => 'Hanya tutup kasir hari ini yang bisa dibatalkan.']);
        }

        DB::transaction(function () use ($closing) {
            foreach (array_filter([$closing->adjustment_journal_id, $closing->draw_journal_id]) as $journalId) {
                Journal::find($journalId)?->delete();
            }
            $closing->delete();
        });
    }

    private static function account(string $code): ?Account
    {
        return Account::where('code', $code)->first();
    }

    private static function requireAccount(string $code, string $name): Account
    {
        $account = self::account($code);
        if ($account === null) {
            throw ValidationException::withMessages(['physical_cash' =>
                "Akun $code $name belum ada. Tambahkan dulu di menu Akun, lalu simpan ulang."]);
        }

        return $account;
    }
}
