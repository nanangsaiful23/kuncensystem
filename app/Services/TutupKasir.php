<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CashClosing;
use App\Models\GoodLoading;
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

    /** Jurnal loading barang dan pembulatannya (type_id = ID loading); dikurangkan dari laci good_loadings.cash_drawer. */
    public const JURNAL_LOADING = ['good_loading', 'good_loading_rounding'];

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
     * - loading: loading barang yang dibayar dari Kas di Tangan dan dipilih
     *   lacinya (good_loadings.cash_drawer), termasuk jurnal pembulatannya.
     * - total = penjualan + lain - biaya - loading.
     * Transaksi lain & biaya lain memakai akun penginput jurnal (journals.created_by_*).
     * Yang tidak masuk laci mana pun (tanpa penginput, loading tanpa laci, jurnal
     * manual, dll.) dikumpulkan di cekJumlahLaci(). Modal kembalian dan
     * penarikan tidak dicatat per laci, jadi selisih resmi tetap dari total semua laci.
     *
     * @return array<string, array{key:string, nama:string, penjualan:float, jumlah:int, lain:float, biaya:float, loading:float, total:float}>
     *         kunci: "cashier:15", "admin:1"
     */
    public static function panduanPerKasir(string $date): array
    {
        $hasil = [];
        $baris = function (string $key) use (&$hasil) {
            return $hasil[$key] ??= ['key' => $key, 'penjualan' => 0.0, 'jumlah' => 0, 'lain' => 0.0, 'biaya' => 0.0, 'loading' => 0.0];
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
            $key = $r->role . ':' . $r->role_id;
            $baris($key);
            $hasil[$key]['penjualan'] = round((float) $r->total, 2);
            $hasil[$key]['jumlah'] = (int) $r->jumlah;
        }

        if (Journal::punyaKolomPembuat()) {
            foreach (self::lainDanBiaya($date) as $r) {
                $key = $r->created_by_role . ':' . $r->created_by_id;
                $baris($key);
                $hasil[$key][$r->jenis] += round((float) $r->total, 2);
            }
        }

        foreach (self::jurnalLoadingKas($date, $date) as $j) {
            if ($j->cash_drawer !== null && $j->cash_drawer !== '') {
                $baris($j->cash_drawer);
                $hasil[$j->cash_drawer]['loading'] = round($hasil[$j->cash_drawer]['loading'] - $j->net, 2);
            }
        }

        foreach ($hasil as $key => $h) {
            $hasil[$key]['nama']  = self::namaLaci($key);
            $hasil[$key]['total'] = round($h['penjualan'] + $h['lain'] - $h['biaya'] - $h['loading'], 2);
        }
        uasort($hasil, fn ($a, $b) => $b['total'] <=> $a['total']);

        return $hasil;
    }

    /** Nama laci dari kuncinya: "cashier:15" -> nama kasir, "admin:1" -> "Nama (admin)". */
    public static function namaLaci(string $key): string
    {
        [$role, $id] = array_pad(explode(':', $key, 2), 2, '');
        if ($role === 'cashier' || $role === 'admin') {
            $user = $role === 'admin' ? \App\Admin::find($id) : \App\Cashier::find($id);

            return ($user->name ?? ucfirst($role) . ' #' . $id) . ($role === 'admin' ? ' (admin)' : '');
        }
        if ($role === 'tambahan') {
            return $id;
        }

        return $key === self::LACI_BAWAAN ? 'Laci' : $key;
    }

    /**
     * Pilihan "Diambil dari laci/kasir" di form loading barang: kasir & admin
     * aktif. Kuncinya sama dengan kunci laci di daftarLaci().
     *
     * @param  string|null  $tetap  pilihan yang sudah tersimpan (tetap tampil walaupun kasirnya sudah tidak aktif)
     * @return array<string, string> kunci => nama
     */
    public static function pilihanLaci(?string $tetap = null): array
    {
        $hasil = [];
        foreach (\App\Cashier::where('is_active', 1)->orderBy('name')->get(['id', 'name']) as $c) {
            $hasil['cashier:' . $c->id] = $c->name;
        }
        foreach (\App\Admin::where('is_active', 1)->orderBy('name')->get(['id', 'name']) as $a) {
            $hasil['admin:' . $a->id] = $a->name . ' (admin)';
        }
        if ($tetap !== null && $tetap !== '' && ! isset($hasil[$tetap])) {
            $hasil[$tetap] = self::namaLaci($tetap);
        }

        return $hasil;
    }

    /**
     * Jurnal loading barang (dan pembulatannya) yang mengenai Kas di Tangan
     * pada rentang tanggal itu, dengan laci loadingnya. Jurnal loading retur
     * dari kasir (tanpa type_id) tidak ikut: sudah masuk penjualan.
     * net = debit 1111 - kredit 1111 (minus = uang keluar dari laci).
     */
    private static function jurnalLoadingKas(string $dari, string $sampai): \Illuminate\Support\Collection
    {
        $kas = self::account(self::KAS_DI_TANGAN);
        if ($kas === null) {
            return collect();
        }

        return DB::table('journals')
            ->join('good_loadings', 'good_loadings.id', '=', 'journals.type_id')
            ->whereNull('journals.deleted_at')
            ->whereIn('journals.type', self::JURNAL_LOADING)
            ->whereBetween('journals.journal_date', [$dari, $sampai])
            ->where(fn ($q) => $q->where('journals.debit_account_id', $kas->id)->orWhere('journals.credit_account_id', $kas->id))
            ->orderBy('journals.id')
            ->get(['journals.id', 'journals.type', 'journals.type_id', 'journals.journal_date', 'journals.name',
                   'journals.debit_account_id', 'journals.debit', 'journals.credit_account_id', 'journals.credit',
                   DB::raw(GoodLoading::punyaKolomLaci() ? 'good_loadings.cash_drawer' : 'NULL AS cash_drawer')])
            ->map(function ($j) use ($kas) {
                $j->net = self::netKas($j, $kas->id);

                return $j;
            });
    }

    /** Pengaruh satu jurnal ke saldo Kas di Tangan: debit 1111 - kredit 1111. */
    private static function netKas(object $j, int $kasId): float
    {
        return round(($j->debit_account_id == $kasId ? (float) $j->debit : 0.0) - ($j->credit_account_id == $kasId ? (float) $j->credit : 0.0), 2);
    }

    /**
     * Cek otomatis: jumlah perkiraan semua laci harus sama dengan saldo sistem.
     *
     * Saldo sistem = saldo akhir tutup kasir sebelumnya + semua jurnal Kas di
     * Tangan sesudahnya sampai tanggal ini. Jumlah perkiraan laci = jumlah sisa
     * kemarin + uang masuk/keluar yang tercatat per laci. Bedanya pasti berasal
     * dari jurnal 1111 yang tidak masuk laci mana pun; semuanya dikumpulkan di
     * 'luar' (jenis jurnal, ID, nominal), supaya sumber bedanya langsung terlihat:
     * - transaction/retur : jurnal penjualan & retur kasir dibanding jumlah penjualan per laci
     * - box/pulsa/piutang/..._transaction, other_payment : tanpa penginput atau kasirnya tidak ikut dihitung
     * - good_loading, good_loading_rounding : loading belum dipilih lacinya
     * - lainnya (jurnal manual, cash_adjustment/cash_draw lama, piutang, dll.) : selalu di luar laci
     * Jurnal selisih & penarikan tutup kasir tanggal ini sendiri tidak dihitung.
     *
     * @param  list<array{key:?string, perkiraan:?float}>  $laci  laci yang dihitung (daftarLaci() atau rincian tutup kasir)
     * @param  CashClosing|null  $closing  tutup kasir tanggal ini kalau sudah ditutup
     * @return array{bisaDicek:bool, jumlah:?float, saldo:float, selisih:?float, cocok:bool,
     *               luar:list<array{jenis:string, id:?int, teks:string, nominal:float, url:?string}>,
     *               keluar:float, masuk:float, belumKetemu:float}
     */
    public static function cekJumlahLaci(string $date, array $laci, float $saldo, ?CashClosing $closing = null, ?array $panduan = null): array
    {
        $kas = self::account(self::KAS_DI_TANGAN);
        $kemarin = self::laciKemarin($date);
        $bisaDicek = $kemarin !== null && $kas !== null;
        $sebelum = $bisaDicek ? CashClosing::where('closing_date', '<', $date)->orderByDesc('closing_date')->orderByDesc('id')->first() : null;
        // Tanpa sisa kemarin hanya jurnal tanggal ini yang dilihat (tidak ada yang dibandingkan).
        $dari = $sebelum ? date('Y-m-d', strtotime($sebelum->closing_date->format('Y-m-d') . ' +1 day')) : $date;

        $keys = [];
        $jumlah = 0.0;
        $penjualanLaci = 0.0;
        $panduan ??= self::panduanPerKasir($date);
        foreach ($laci as $l) {
            $key = $l['key'] ?? null;
            if ($key === null || $l['perkiraan'] === null) {
                continue; // laci tambahan baru: tidak ada perkiraannya
            }
            $keys[$key] = true;
            $jumlah += (float) $l['perkiraan'];
            $penjualanLaci += $panduan[$key]['penjualan'] ?? 0.0;
        }

        $luar = [];
        $jurnalPenjualan = 0.0;
        if ($kas !== null) {
            $loadingLaci = [];
            foreach (self::jurnalLoadingKas($dari, $date) as $j) {
                $loadingLaci[$j->id] = $j->cash_drawer;
            }

            $jurnal = DB::table('journals')
                ->whereNull('deleted_at')
                ->whereBetween('journal_date', [$dari, $date])
                ->where(fn ($q) => $q->where('debit_account_id', $kas->id)->orWhere('credit_account_id', $kas->id))
                ->orderBy('journal_date')->orderBy('id')
                ->get();
            $pembuat = Journal::punyaKolomPembuat();
            $milikTutupKasir = $closing ? array_filter([$closing->adjustment_journal_id, $closing->draw_journal_id]) : [];

            foreach ($jurnal as $j) {
                $net = self::netKas($j, $kas->id);
                if ($net == 0 || in_array((int) $j->id, $milikTutupKasir, true)) {
                    continue;
                }
                $hariIni = $j->journal_date === $date;
                $penginput = $pembuat && $j->created_by_role !== null ? $j->created_by_role . ':' . $j->created_by_id : null;

                // Penjualan & retur kasir (jurnal harian tanpa type_id): dibandingkan totalnya di bawah.
                $jurnalJual = ($j->type === 'transaction' && $j->type_id === null && $j->debit_account_id == $kas->id)
                    || ($j->type === 'good_loading' && $j->type_id === null && $j->credit_account_id == $kas->id);
                if ($hariIni && $jurnalJual) {
                    $jurnalPenjualan += $net;
                    continue;
                }

                $ket = null;
                if (array_key_exists($j->id, $loadingLaci)) {
                    $laciLoading = $loadingLaci[$j->id];
                    if ($hariIni && $laciLoading !== null && isset($keys[$laciLoading])) {
                        continue;
                    }
                    $ket = $laciLoading === null || $laciLoading === ''
                        ? 'loading ID ' . $j->type_id . ' belum dipilih lacinya'
                        : 'loading ID ' . $j->type_id . ' dari laci ' . self::namaLaci($laciLoading) . ', laci itu tidak ikut dihitung';
                } elseif ((self::jenisLainBiaya($j->type) === 'lain' && $j->debit_account_id == $kas->id)
                          || (self::jenisLainBiaya($j->type) === 'biaya' && $j->credit_account_id == $kas->id)) {
                    if ($hariIni && $penginput !== null && isset($keys[$penginput])) {
                        continue;
                    }
                    $ket = $penginput === null ? 'tidak tercatat kasirnya'
                        : 'diinput ' . self::namaLaci($penginput) . ', lacinya tidak ikut dihitung';
                }
                if (! $hariIni) {
                    $ket = 'tanggal ' . displayDate($j->journal_date) . ', belum ada tutup kasir tanggal itu' . ($ket ? '; ' . $ket : '');
                }

                $luar[] = [
                    'jenis'   => (string) $j->type,
                    'id'      => (int) $j->id,
                    'teks'    => $j->type . ' #' . $j->id . ': ' . $j->name . ($ket ? ' (' . $ket . ')' : ''),
                    'nominal' => $net,
                    'url'     => in_array($j->type, self::JURNAL_LOADING, true) && $j->type_id
                        ? 'admin/good-loading/' . $j->type_id . '/edit'
                        : 'admin/journal/all/' . rawurlencode((string) $j->type) . '/' . $j->journal_date . '/' . $j->journal_date . '/id/asc/50',
                ];
            }
        }

        $bedaJual = round($jurnalPenjualan - $penjualanLaci, 2);
        if (abs($bedaJual) >= 0.01) {
            $luar[] = [
                'jenis' => 'transaction', 'id' => null, 'nominal' => $bedaJual,
                'teks'  => 'Penjualan cash: jurnal penjualan & retur Kas di Tangan ' . showRupiah($jurnalPenjualan)
                    . ', penjualan cash per laci ' . showRupiah($penjualanLaci)
                    . ' (transaksi tanpa kasir, kasirnya tidak ikut dihitung, atau jurnal penjualan tidak sama dengan transaksi: lihat cek Penjualan)',
                'url'   => 'admin/journal/all/transaction/' . $date . '/' . $date . '/id/asc/50',
            ];
        }

        if ($bisaDicek) {
            $sisaKemarin = round(array_sum(array_column($kemarin, 'sisa')), 2);
            $saldoKemarin = self::saldoKasDiTangan($sebelum->closing_date->format('Y-m-d'));
            $bedaAwal = round($saldoKemarin - $sisaKemarin, 2);
            if (abs($bedaAwal) >= 0.01) {
                $luar[] = [
                    'jenis' => 'saldo_awal', 'id' => null, 'nominal' => $bedaAwal, 'url' => null,
                    'teks'  => 'Saldo Kas di Tangan akhir ' . displayDate($sebelum->closing_date) . ' sekarang ' . showRupiah($saldoKemarin)
                        . ', sisa semua laci saat tutup kasir itu ' . showRupiah($sisaKemarin)
                        . ' (ada jurnal tanggal itu atau sebelumnya yang dibuat/diubah setelah tutup kasir)',
                ];
            }
        }

        $nominal = array_column($luar, 'nominal');
        $keluar = round(array_sum(array_filter($nominal, fn ($n) => $n < 0)), 2);
        $masuk  = round(array_sum(array_filter($nominal, fn ($n) => $n > 0)), 2);
        $jumlah = $bisaDicek ? round($jumlah, 2) : null;
        $selisih = $bisaDicek ? round($saldo - $jumlah, 2) : null;

        return [
            'bisaDicek'   => $bisaDicek,
            'jumlah'      => $jumlah,
            'saldo'       => round($saldo, 2),
            'selisih'     => $selisih,
            'cocok'       => $bisaDicek && abs($selisih) < 0.01,
            'luar'        => $luar,
            'keluar'      => $keluar,
            'masuk'       => $masuk,
            'belumKetemu' => $bisaDicek ? round($selisih - $keluar - $masuk, 2) : 0.0,
        ];
    }

    /**
     * Rincian laci tutup kasir yang sudah disimpan, dengan uang masuk/keluar
     * hari itu dihitung ulang dari data sekarang (mis. laci loading baru dipilih
     * setelah tutup kasir). Sisa kemarin, uang fisik, dan penarikan tetap dari
     * saat tutup kasir. Rincian versi lama (tanpa kunci laci) ditampilkan apa adanya.
     *
     * @return list<array{key:?string, nama:string, sisa_kemarin:?float, penjualan:?float, lain:?float, biaya:?float,
     *                    loading:?float, masuk:?float, perkiraan:?float, uang:float, tarik:?float}>
     */
    public static function laciTutupKasir(CashClosing $closing, ?array $panduan = null): array
    {
        $panduan ??= self::panduanPerKasir($closing->closing_date->format('Y-m-d'));
        $hasil = [];
        foreach ($closing->drawers ?? [] as $l) {
            $key = $l['key'] ?? null;
            $baris = [
                'key' => $key, 'nama' => $l['nama'], 'sisa_kemarin' => $l['sisa_kemarin'] ?? null,
                'uang' => (float) $l['uang'], 'tarik' => array_key_exists('tarik', $l) ? (float) $l['tarik'] : null,
            ];
            $tambahanBaru = $key === null || (str_starts_with($key, 'tambahan:') && ($l['sisa_kemarin'] ?? null) === null);
            if ($key === null) {
                $masuk = array_key_exists('panduan', $l) ? $l['panduan'] : ($l['penjualan_cash'] ?? null);
                $hasil[] = $baris + ['penjualan' => $l['penjualan_cash'] ?? null, 'lain' => $l['lain_cash'] ?? null, 'biaya' => $l['biaya_cash'] ?? null,
                                     'loading' => null, 'masuk' => $masuk, 'perkiraan' => $l['perkiraan'] ?? null];
                continue;
            }
            $p = $panduan[$key] ?? ['penjualan' => 0.0, 'lain' => 0.0, 'biaya' => 0.0, 'loading' => 0.0, 'total' => 0.0];
            $hasil[] = $baris + [
                'penjualan' => $tambahanBaru ? null : $p['penjualan'], 'lain' => $tambahanBaru ? null : $p['lain'],
                'biaya' => $tambahanBaru ? null : $p['biaya'], 'loading' => $tambahanBaru ? null : $p['loading'],
                'masuk' => $tambahanBaru ? null : $p['total'],
                'perkiraan' => $tambahanBaru || $baris['sisa_kemarin'] === null ? null : round($baris['sisa_kemarin'] + $p['total'], 2),
            ];
        }

        return $hasil;
    }

    /**
     * Saldo Kas di Tangan sekarang sampai akhir tanggal tutup kasir itu, tanpa
     * jurnal selisih & penarikannya sendiri (= saldo sistem kalau tidak ada data
     * yang berubah setelah tutup kasir).
     */
    public static function saldoSebelumTutup(CashClosing $closing): float
    {
        $kas = self::account(self::KAS_DI_TANGAN);
        $saldo = self::saldoKasDiTangan($closing->closing_date->format('Y-m-d'));
        if ($kas === null) {
            return $saldo;
        }
        foreach (array_filter([$closing->adjustment_journal_id, $closing->draw_journal_id]) as $id) {
            $j = Journal::find($id);
            if ($j !== null && $j->journal_date <= $closing->closing_date->format('Y-m-d')) {
                $saldo -= self::netKas($j, $kas->id);
            }
        }

        return round($saldo, 2);
    }

    /**
     * Jurnal transaksi lain (masuk 1111) & biaya lain (keluar 1111) per penginput.
     * Pola LIKE-nya sama dengan jenisLainBiaya().
     */
    private static function lainDanBiaya(string $date)
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
                ->where($kolomAkun, $kas->id)
                ->whereNotNull('created_by_role')
                ->groupBy('created_by_role', 'created_by_id')
                ->selectRaw("created_by_role, created_by_id, SUM($kolomUang) AS total, '$jenis' AS jenis");
            foreach ($q->get() as $r) {
                $hasil[] = $r;
            }
        }

        return $hasil;
    }

    /**
     * 'lain' / 'biaya' kalau jenis jurnal cocok dengan pola LIKE di lainDanBiaya()
     * ('%_transaction', '%_payment%'; "_" di LIKE = satu huruf apa saja), selain itu null.
     */
    private static function jenisLainBiaya(?string $type): ?string
    {
        $t = strtolower((string) $type);
        if (strlen($t) > 11 && str_ends_with($t, 'transaction')) {
            return 'lain';
        }
        $pos = strpos($t, 'payment');

        return $pos !== false && $pos >= 1 ? 'biaya' : null;
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
    public static function daftarLaci(string $date, ?array $panduan = null): array
    {
        $kemarin = self::laciKemarin($date);
        $panduan ??= self::panduanPerKasir($date);
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
