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
 * Diisi di halaman Resume Transaksi Total untuk satu tanggal.
 *
 * 1. Uang fisik di laci dibandingkan dengan saldo Kas di Tangan (1111)
 *    sampai akhir tanggal itu.
 * 2. Selisihnya langsung dijurnal supaya saldo sistem = uang fisik:
 *    kurang -> debit 5219 Biaya Uang Hilang, kredit 1111;
 *    lebih  -> debit 1111, kredit 6101 Pendapatan Lain-lain.
 * 3. Uang yang diserahkan ke owner dijurnal seperti Penarikan Uang lama
 *    (cash_draw: debit 1113 Kas di Nanang, kredit 1111).
 *
 * Tutup kasir berurutan: satu per tanggal, dan tanggal lampau (lupa input)
 * hanya boleh diisi kalau sesudahnya belum ada tutup kasir. Kalau tidak,
 * selisih tutup kasir sesudahnya dihitung dari saldo yang sudah berubah.
 * Karena itu hanya tutup kasir terakhir yang bisa dibatalkan.
 */
class TutupKasir
{
    public const KAS_DI_TANGAN = '1111';
    public const KAS_DI_NANANG = '1113';
    public const UANG_HILANG   = '5219';
    public const PENDAPATAN_LAIN = '6101';

    /** Kunci laci bawaan (satu laci) kalau di tanggal itu belum ada kasir dengan uang cash masuk/keluar. */
    public const LACI_BAWAAN = 'laci:1';

    /** Perangkat Kasir HP/PC yang tidak terhubung selama ini (menit) diberi peringatan. */
    public const DEVICE_STALE_MINUTES = 5;

    /**
     * Saldo Kas di Tangan menurut sistem sampai akhir tanggal $sampai
     * (bawaan hari ini): saldo awal akun + debit - kredit jurnal sampai tanggal itu.
     */
    public static function saldoKasDiTangan(?string $sampai = null): float
    {
        $account = self::account(self::KAS_DI_TANGAN);
        if ($account === null) {
            return 0.0;
        }

        $sampai ??= date('Y-m-d');
        $sampaiTanggal = fn ($q) => $q->where('journal_date', '<=', $sampai)->orWhereNull('journal_date');

        $debit  = (float) Journal::where('debit_account_id', $account->id)->where($sampaiTanggal)->sum('debit');
        $credit = (float) Journal::where('credit_account_id', $account->id)->where($sampaiTanggal)->sum('credit');

        return round((float) $account->balance + $debit - $credit, 2);
    }

    /**
     * Panduan per laci: uang cash yang masuk/keluar lewat akun (kasir/admin)
     * itu pada tanggal itu.
     * - penjualan: transaksi cash normal + retur (seperti "Total transaksi cash"
     *   di Resume Total). Transaksi Kasir HP/PC tercatat atas nama kasir yang
     *   login, bertanggal jual asli.
     * - lain: Transaksi Lain yang masuk Kas di Tangan (jurnal %_transaction, mis.
     *   plastik/kardus, pulsa cash, bayar piutang cash).
     * - biaya: Biaya Lain yang dibayar dari Kas di Tangan (jurnal %_payment%).
     * - total = penjualan + lain - biaya.
     * Transaksi lain & biaya lain memakai akun penginput jurnal (journals.created_by_*);
     * jurnal tanpa penginput dihitung di tanpaKasir(). Modal kembalian dan
     * penarikan tidak dicatat per laci, jadi selisih resmi tetap dari total semua laci.
     *
     * @return array<string, array{key:string, nama:string, penjualan:float, jumlah:int, lain:float, biaya:float, total:float}>
     *         kunci: "cashier:15", "admin:1"
     */
    public static function panduanPerKasir(string $date): array
    {
        $hasil = [];
        $baris = function (string $role, $id) use (&$hasil) {
            $key = $role . ':' . $id;

            return $hasil[$key] ??= ['key' => $key, 'role' => $role, 'id' => (int) $id,
                'penjualan' => 0.0, 'jumlah' => 0, 'lain' => 0.0, 'biaya' => 0.0];
        };

        $penjualan = DB::table('transactions')
            ->whereNull('deleted_at')
            ->whereIn('type', ['normal', 'retur'])
            ->where('payment', 'cash')
            ->whereDate('created_at', $date)
            ->whereNotNull('role_id')
            ->groupBy('role', 'role_id')
            ->selectRaw('role, role_id, SUM(total_sum_price) AS total, COUNT(*) AS jumlah')
            ->get();
        foreach ($penjualan as $r) {
            $baris((string) $r->role, $r->role_id);
            $hasil[$r->role . ':' . $r->role_id]['penjualan'] = round((float) $r->total, 2);
            $hasil[$r->role . ':' . $r->role_id]['jumlah'] = (int) $r->jumlah;
        }

        if (Journal::punyaKolomPembuat()) {
            foreach (self::lainDanBiaya($date, true) as $r) {
                $baris((string) $r->created_by_role, $r->created_by_id);
                $hasil[$r->created_by_role . ':' . $r->created_by_id][$r->jenis] += round((float) $r->total, 2);
            }
        }

        foreach ($hasil as $key => $h) {
            $user = $h['role'] === 'admin' ? \App\Admin::find($h['id']) : \App\Cashier::find($h['id']);
            $hasil[$key]['nama']  = ($user->name ?? ucfirst($h['role']) . ' #' . $h['id']) . ($h['role'] === 'admin' ? ' (admin)' : '');
            $hasil[$key]['total'] = round($h['penjualan'] + $h['lain'] - $h['biaya'], 2);
            unset($hasil[$key]['role'], $hasil[$key]['id']);
        }
        uasort($hasil, fn ($a, $b) => $b['total'] <=> $a['total']);

        return $hasil;
    }

    /**
     * Transaksi lain & biaya lain cash pada tanggal itu yang tidak tercatat
     * penginputnya (dibuat sebelum kolom penginput ada).
     *
     * @return array{lain: float, biaya: float}
     */
    public static function tanpaKasir(string $date): array
    {
        $hasil = ['lain' => 0.0, 'biaya' => 0.0];
        foreach (self::lainDanBiaya($date, false) as $r) {
            $hasil[$r->jenis] += round((float) $r->total, 2);
        }

        return $hasil;
    }

    /** Jurnal transaksi lain (masuk 1111) & biaya lain (keluar 1111) per penginput, atau yang tanpa penginput. */
    private static function lainDanBiaya(string $date, bool $perPenginput)
    {
        $kas = self::account(self::KAS_DI_TANGAN);
        if ($kas === null) {
            return [];
        }

        $hasil = [];
        foreach (['lain' => ['%_transaction', 'debit_account_id', 'debit'], 'biaya' => ['%_payment%', 'credit_account_id', 'credit']] as $jenis => [$pola, $kolomAkun, $kolomUang]) {
            $q = DB::table('journals')
                ->whereNull('deleted_at')
                ->where('journal_date', $date)
                ->where('type', 'like', $pola)
                ->where($kolomAkun, $kas->id);

            if ($perPenginput) {
                $q->whereNotNull('created_by_role')->groupBy('created_by_role', 'created_by_id')
                  ->selectRaw("created_by_role, created_by_id, SUM($kolomUang) AS total, '$jenis' AS jenis");
            } else {
                if (Journal::punyaKolomPembuat()) {
                    $q->whereNull('created_by_role');
                }
                $q->selectRaw("SUM($kolomUang) AS total, '$jenis' AS jenis");
            }
            foreach ($q->get() as $r) {
                $hasil[] = $r;
            }
        }

        return $hasil;
    }

    /**
     * Sisa uang per laci dari tutup kasir terakhir sebelum tanggal ini
     * (uang fisik - ditarik). Kasir tetap di lacinya, jadi laci = akun kasir.
     * null kalau belum ada tutup kasir dengan rincian penarikan per laci
     * (sisa kemarin tidak diketahui).
     *
     * @return array<string, array{key:string, nama:string, sisa:float}>|null
     */
    public static function laciKemarin(string $date): ?array
    {
        $sebelum = CashClosing::where('closing_date', '<', $date)->orderByDesc('closing_date')->orderByDesc('id')->first();
        if ($sebelum === null || ! $sebelum->drawers) {
            return null;
        }

        $hasil = [];
        foreach ($sebelum->drawers as $l) {
            if (! isset($l['key']) || ! array_key_exists('tarik', $l)) {
                return null; // rincian versi lama (tanpa penarikan per laci)
            }
            $hasil[$l['key']] = ['key' => $l['key'], 'nama' => $l['nama'], 'sisa' => round($l['uang'] - $l['tarik'], 2)];
        }

        return $hasil;
    }

    /**
     * Laci yang harus dihitung pada tanggal itu: laci yang kemarin masih ada
     * sisanya (walaupun hari ini tidak ada transaksi) + kasir/admin yang ada uang
     * cash masuk/keluar hari ini. Kalau tidak ada, satu laci bawaan.
     * perkiraan = sisa kemarin + uang masuk hari ini (null kalau sisa kemarin tidak diketahui).
     *
     * @return list<array{key:string, nama:string, sisa:?float, p:?array, perkiraan:?float}>
     */
    public static function daftarLaci(string $date): array
    {
        $kemarin = self::laciKemarin($date);
        $panduan = self::panduanPerKasir($date);
        $sisa = fn (string $key) => $kemarin === null ? null : ($kemarin[$key]['sisa'] ?? 0.0);

        $baris = [];
        foreach ($kemarin ?? [] as $key => $k) {
            if ($k['sisa'] != 0 || isset($panduan[$key])) {
                $baris[$key] = ['key' => $key, 'nama' => $panduan[$key]['nama'] ?? $k['nama'], 'sisa' => $k['sisa'], 'p' => $panduan[$key] ?? null];
            }
        }
        foreach ($panduan as $key => $p) {
            $baris[$key] ??= ['key' => $key, 'nama' => $p['nama'], 'sisa' => $sisa($key), 'p' => $p];
        }
        if (! $baris) {
            $baris[self::LACI_BAWAAN] = ['key' => self::LACI_BAWAAN, 'nama' => 'Laci', 'sisa' => $sisa(self::LACI_BAWAAN), 'p' => null];
        }

        foreach ($baris as $key => $b) {
            $baris[$key]['perkiraan'] = $b['sisa'] === null ? null : round($b['sisa'] + ($b['p']['total'] ?? 0), 2);
        }

        return array_values($baris);
    }

    public static function forDate(string $date): ?CashClosing
    {
        return CashClosing::whereDate('closing_date', $date)->latest('id')->first();
    }

    /** Tutup kasir dengan tanggal paling akhir. */
    public static function last(): ?CashClosing
    {
        return CashClosing::orderByDesc('closing_date')->orderByDesc('id')->first();
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
     * Simpan tutup kasir untuk tanggal $date (bawaan hari ini).
     *
     * @param  float|null  $saldoDilihat  saldo sistem yang tampil saat form dibuka;
     *                                     kalau sudah berubah (ada transaksi baru), ditolak.
     * @param  array  $laci  rincian per laci [['nama' => ..., 'uang' => ..., 'panduan' => ..., ...]];
     *                       kalau diisi, $uangFisik = jumlah uang semua laci.
     */
    public static function close(float $uangFisik, float $diserahkan, ?string $note, string $role, int $roleId, ?float $saldoDilihat = null, ?string $date = null, array $laci = []): CashClosing
    {
        $date ??= date('Y-m-d');

        if ($date > date('Y-m-d')) {
            throw ValidationException::withMessages(['closing_date' => 'Tanggal tutup kasir tidak boleh setelah hari ini.']);
        }
        if ($uangFisik < 0) {
            throw ValidationException::withMessages(['physical_cash' => 'Uang fisik tidak boleh minus.']);
        }
        if ($diserahkan < 0) {
            throw ValidationException::withMessages(['draw_amount' => 'Jumlah yang diserahkan tidak boleh minus.']);
        }
        if ($diserahkan > $uangFisik) {
            throw ValidationException::withMessages(['draw_amount' => 'Jumlah yang diserahkan ke owner melebihi uang fisik di laci.']);
        }

        foreach ($laci as $l) {
            if ($l['uang'] < 0) {
                throw ValidationException::withMessages(['physical_cash' => 'Uang di laci ' . $l['nama'] . ' tidak boleh minus.']);
            }
            if (($l['tarik'] ?? 0) < 0) {
                throw ValidationException::withMessages(['draw_amount' => 'Penarikan dari laci ' . $l['nama'] . ' tidak boleh minus.']);
            }
            if (($l['tarik'] ?? 0) > $l['uang']) {
                throw ValidationException::withMessages(['draw_amount' => 'Penarikan dari laci ' . $l['nama'] . ' melebihi uang fisik di laci itu.']);
            }
        }
        if ($laci && isset($laci[0]['tarik']) && abs(array_sum(array_column($laci, 'tarik')) - $diserahkan) >= 0.01) {
            throw ValidationException::withMessages(['draw_amount' => 'Jumlah penarikan per laci tidak sama dengan total penarikan.']);
        }
        if ($laci && abs(array_sum(array_column($laci, 'uang')) - $uangFisik) >= 0.01) {
            throw ValidationException::withMessages(['physical_cash' => 'Jumlah uang per laci tidak sama dengan total uang fisik.']);
        }

        return DB::transaction(function () use ($date, $uangFisik, $diserahkan, $note, $role, $roleId, $saldoDilihat, $laci) {
            $kas = self::requireAccount(self::KAS_DI_TANGAN, 'Kas di Tangan');

            // Kunci baris akun Kas di Tangan: dua tutup kasir bersamaan menunggu
            // satu sama lain, jadi yang kedua melihat yang pertama sudah tersimpan.
            Account::whereKey($kas->id)->lockForUpdate()->first();

            if (self::forDate($date) !== null) {
                throw ValidationException::withMessages(['closing_date' => 'Kasir tanggal ' . displayDate($date) . ' sudah ditutup.']);
            }
            $last = self::last();
            if ($last !== null && $last->closing_date->format('Y-m-d') > $date) {
                throw ValidationException::withMessages(['closing_date' =>
                    'Sudah ada tutup kasir tanggal ' . displayDate($last->closing_date) . '. Tutup kasir hanya bisa diisi untuk tanggal sesudahnya.']);
            }

            $saldo = self::saldoKasDiTangan($date);
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
                'drawers'        => $laci ? array_values($laci) : null,
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

    /** Batalkan tutup kasir terakhir: hapus jurnalnya, lalu tanggal itu bisa ditutup ulang. */
    public static function cancel(CashClosing $closing): void
    {
        $last = self::last();
        if ($last === null || $last->id !== $closing->id) {
            throw ValidationException::withMessages(['cancel' =>
                'Hanya tutup kasir terakhir' . ($last ? ' (tanggal ' . displayDate($last->closing_date) . ')' : '') . ' yang bisa dibatalkan.']);
        }

        DB::transaction(function () use ($closing) {
            foreach (array_filter([$closing->adjustment_journal_id, $closing->draw_journal_id]) as $journalId) {
                Journal::find($journalId)?->delete();
            }
            // Hasil cek kecocokannya ikut hilang; tutup kasir ulang menjalankan cek baru.
            if (\App\Models\ReconciliationCheck::tabelAda()) {
                \App\Models\ReconciliationCheck::where('cash_closing_id', $closing->id)->delete();
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
