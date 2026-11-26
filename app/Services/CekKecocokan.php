<?php

namespace App\Services;

use App\Models\CashClosing;
use App\Models\Good;
use App\Models\PosDevice;
use App\Models\PosSyncReject;
use App\Models\ReconciliationCheck;
use App\Models\StockMinusLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cek kecocokan harian (docs/rencana-optimasi-dan-tutup-kasir.md poin 3).
 * Berjalan otomatis setiap kali Tutup Kasir disimpan, jadi tidak perlu
 * scheduler. Hasil yang tidak cocok tampil di dashboard admin sampai
 * ditandai sudah dibaca / sudah ditangani.
 *
 * Yang dicek untuk tanggal tutup kasir:
 * - kas       : uang fisik vs saldo Kas di Tangan (1111) sebelum penyesuaian
 * - penjualan : transaksi hari itu vs jurnal Penjualan (4101)
 * - hpp       : HPP transaksi hari itu vs jurnal HPP (5101), termasuk retur & batal transaksi
 * - stok      : barang yang bergerak hari itu: stok tercatat beda dengan hitungan
 *               dari loading & transaksi (StockService), atau stoknya minus
 * - kasir_hp  : transaksi Kasir HP/PC yang ditolak saat sinkron dan belum diselesaikan
 *
 * Hanya membaca data; yang ditulis hanya hasilnya (reconciliation_checks).
 * Aturan penjualan/HPP sama dengan `php artisan kuncen:audit` (perintah itu
 * juga memakai selisihJurnal()). Tabel transaksi tidak punya indeks tanggal,
 * jadi dibaca sekali saja untuk semua cek.
 */
class CekKecocokan
{
    /** Selisih uang kurang dari ini (rupiah) dianggap pembulatan. */
    public const TOLERANSI = 1;

    /** Rincian yang disimpan per cek; sisanya hanya dihitung ("dan N lainnya"). */
    public const MAKS_RINCIAN = 30;

    /** MariaDB mengubah IN (...) berisi 1000 nilai atau lebih menjadi subquery (di server lokal hasilnya kosong). */
    private const CHUNK = 500;

    /**
     * Jalankan semua cek untuk tutup kasir ini dan simpan hasilnya.
     * Tidak menggagalkan tutup kasir: kalau ceknya error, error itu dicatat sebagai masalah.
     */
    public static function setelahTutupKasir(CashClosing $closing): ?ReconciliationCheck
    {
        if (! ReconciliationCheck::tabelAda()) {
            return null;
        }
        $date = $closing->closing_date->format('Y-m-d');

        return ReconciliationCheck::create(['check_date' => $date, 'cash_closing_id' => $closing->id]
            + self::isian(self::periksaAman($date, $closing)));
    }

    /**
     * Jalankan ulang cek (setelah data diperbaiki). Kalau sekarang semuanya
     * cocok, otomatis ditandai sudah ditangani.
     */
    public static function cekUlang(ReconciliationCheck $check, string $role, int $roleId): ReconciliationCheck
    {
        $closing = $check->cash_closing_id ? CashClosing::find($check->cash_closing_id) : null;
        $check->fill(self::isian(self::periksaAman($check->check_date->format('Y-m-d'), $closing)));
        $check->updated_at = now(); // waktu "dicek" ikut baru walaupun hasilnya sama

        if ($check->problem_count === 0 && $check->resolved_at === null) {
            $check->fill([
                'resolved_at' => now(), 'resolution' => ReconciliationCheck::DITANGANI,
                'resolved_by_role' => $role, 'resolved_by_id' => $roleId, 'resolve_note' => 'Cek ulang: semua sudah cocok.',
            ]);
        }
        $check->save();

        return $check;
    }

    /** Semua cek untuk satu tanggal. Cek kas hanya kalau ada tutup kasirnya. */
    public static function periksa(string $date, ?CashClosing $closing = null): array
    {
        $trx = self::transaksiPeriode($date, $date);
        $jurnal = collect(self::selisihJurnal($date, $date, $trx));

        return array_values(array_filter([
            $closing ? self::cekKas($closing) : null,
            self::cekJurnal('penjualan', 'Penjualan', $jurnal->where('jenis', 'penjualan'), $date,
                'Total transaksi sama dengan jurnal Penjualan (4101).', 'transaction'),
            self::cekJurnal('hpp', 'HPP', $jurnal->where('jenis', 'hpp'), $date,
                'HPP transaksi sama dengan jurnal HPP (5101).', 'hpp'),
            self::cekStok($date, $trx),
            self::cekKasirHp(),
        ]));
    }

    private static function periksaAman(string $date, ?CashClosing $closing): array
    {
        try {
            return self::periksa($date, $closing);
        } catch (\Throwable $e) {
            report($e);

            return [self::hasil('error', 'Cek kecocokan', false, 'Cek gagal dijalankan: ' . Str::limit($e->getMessage(), 300)
                . '. Klik "Cek ulang" untuk mencoba lagi.')];
        }
    }

    private static function isian(array $hasil): array
    {
        return ['results' => $hasil, 'problem_count' => count(array_filter($hasil, fn ($h) => ! $h['ok']))];
    }

    private static function hasil(string $key, string $label, bool $ok, string $pesan, ?string $url = null, array $rincian = [], array $tambahan = []): array
    {
        return [
            'key' => $key, 'label' => $label, 'ok' => $ok, 'pesan' => $pesan, 'url' => $url,
            'rincian' => array_slice($rincian, 0, self::MAKS_RINCIAN),
            'lainnya' => max(count($rincian) - self::MAKS_RINCIAN, 0),
        ] + $tambahan;
    }

    // ------------------------------------------------------------------ kas

    private static function cekKas(CashClosing $closing): array
    {
        $date = $closing->closing_date->format('Y-m-d');
        $url  = 'admin/transaction/resumeTotal/' . $date . '/' . $date . '#tutup-kasir';
        $selisih = round((float) $closing->difference, 2);

        if ($selisih == 0) {
            return self::hasil('kas', 'Kas', true,
                'Uang fisik ' . showRupiah($closing->physical_cash) . ' sama dengan saldo Kas di Tangan.', $url);
        }

        $rincian = [];
        foreach ($closing->drawers ?? [] as $l) {
            if (! isset($l['perkiraan'], $l['uang'])) {
                continue;
            }
            $s = round($l['uang'] - $l['perkiraan'], 2);
            if ($s != 0) {
                $rincian[] = ['teks' => 'Laci ' . $l['nama'] . ': uang fisik ' . showRupiah($l['uang']) . ', perkiraan '
                    . showRupiah($l['perkiraan']) . ' (' . ($s < 0 ? 'kurang ' : 'lebih ') . showRupiah(abs($s)) . ')', 'url' => null];
            }
        }
        if ($closing->note) {
            $rincian[] = ['teks' => 'Keterangan tutup kasir: ' . $closing->note, 'url' => null];
        }

        return self::hasil('kas', 'Kas', false,
            'Uang fisik ' . showRupiah($closing->physical_cash) . ', saldo Kas di Tangan ' . showRupiah($closing->system_balance) . ': '
            . ($selisih < 0
                ? 'kurang ' . showRupiah(abs($selisih)) . ' (sudah dijurnal ke Biaya Uang Hilang).'
                : 'lebih ' . showRupiah($selisih) . ' (sudah dijurnal ke Pendapatan Lain-lain).'),
            $url, $rincian);
    }

    // ------------------------------------------------------- penjualan & HPP

    private static function cekJurnal(string $key, string $label, Collection $baris, string $date, string $pesanCocok, string $jenisJurnal): array
    {
        $url = 'admin/journal/all/' . $jenisJurnal . '/' . $date . '/' . $date . '/id/asc/50';
        if ($baris->isEmpty()) {
            return self::hasil($key, $label, true, $pesanCocok, $url);
        }

        $rincian = $baris->map(function ($r) use ($url) {
            // "selisih -96.000" dari kuncen:audit ditulis ulang dengan format Rupiah.
            $ket = $r['keterangan'];
            if ($r['seharusnya'] !== null && str_starts_with($ket, 'selisih ')) {
                $s = $r['aktual'] - $r['seharusnya'];
                $ket = 'jurnal ' . ($s < 0 ? 'kurang ' : 'lebih ') . showRupiah(abs($s));
            }

            return [
                'teks' => $r['cek'] . ': '
                    . ($r['seharusnya'] !== null ? 'seharusnya ' . showRupiah($r['seharusnya']) . ', di jurnal ' : 'total ')
                    . showRupiah($r['aktual']) . ($ket !== '' ? ' (' . $ket . ')' : ''),
                'url' => $r['transaction_id'] ? 'admin/transaction/' . $r['transaction_id'] . '/detail' : $url,
            ];
        })->values()->all();

        return self::hasil($key, $label, false, count($rincian) . ' hal tidak cocok dengan jurnal ' . ($key === 'hpp' ? 'HPP (5101)' : 'Penjualan (4101)') . '.', $url, $rincian);
    }

    /**
     * Selisih penjualan, HPP, retur dan batal transaksi terhadap jurnal per
     * tanggal (dulu ditulis langsung di `php artisan kuncen:audit`).
     *
     * @param  Collection|null  $trx  hasil transaksiPeriode() kalau sudah dibaca
     * @return list<array{tanggal:string, jenis:string, cek:string, seharusnya:?float, aktual:float, keterangan:string, transaction_id:?int}>
     */
    public static function selisihJurnal(string $from, string $to, ?Collection $trx = null): array
    {
        $acc = DB::table('accounts')->whereNull('deleted_at')->pluck('id', 'code');
        $akun = fn (string $code) => $acc[$code] ?? 0;
        $tanggal = fn (?string $waktu) => $waktu === null ? null : substr($waktu, 0, 10);
        $dalam = fn (?string $waktu) => $waktu !== null && $tanggal($waktu) >= $from && $tanggal($waktu) <= $to;

        $trx = ($trx ?? self::transaksiPeriode($from, $to))->whereNull('deleted_at');
        $dibuat = $trx->filter(fn ($t) => $dalam($t->created_at))->sortBy('id');
        // Masih dihitung terjual hari itu, juga yang dibatalkan belakangan (pembatalannya punya jurnal sendiri).
        $terjual = $dibuat->filter(fn ($t) => in_array($t->type, ['normal', 'retur', 'not valid', 'deleted'], true));

        $detail = self::ringkasDetail($trx->pluck('id')->all());
        $d = fn ($t, string $k) => (float) ($detail[$t->id]->$k ?? 0);
        // Uang yang dijurnal saat jual: barang terjual - diskon - voucher.
        $uang = fn ($t) => $d($t, 'jual') - (float) ($t->total_discount_price ?? 0) - (float) ($t->voucher_nominal ?? 0);
        $fmt = fn ($v) => number_format($v, 0, ',', '.');

        $baris = [];
        $tambah = function (string $tgl, string $jenis, string $cek, ?float $seharusnya, float $aktual, string $ket, ?int $trxId = null) use (&$baris) {
            $baris[] = ['tanggal' => $tgl, 'jenis' => $jenis, 'cek' => $cek, 'seharusnya' => $seharusnya,
                'aktual' => $aktual, 'keterangan' => $ket, 'transaction_id' => $trxId];
        };
        $perTanggal = function (Collection $list, callable $nilai) use ($tanggal) {
            $hasil = [];
            foreach ($list as $t) {
                $hasil[$tanggal($t->created_at)] = ($hasil[$tanggal($t->created_at)] ?? 0) + $nilai($t);
            }

            return $hasil;
        };
        $bandingkan = function (array $harap, array $jurnal, callable $kalauBeda) {
            $semua = array_unique(array_merge(array_keys($harap), array_keys($jurnal)));
            sort($semua);
            foreach ($semua as $tgl) {
                $e = (float) ($harap[$tgl] ?? 0);
                $j = (float) ($jurnal[$tgl] ?? 0);
                if (abs($e - $j) >= self::TOLERANSI) {
                    $kalauBeda((string) $tgl, $e, $j);
                }
            }
        };
        $jurnalHarian = fn ($q, string $kolom) => $q->whereNull('deleted_at')->whereBetween('journal_date', [$from, $to])
            ->groupBy('journal_date')->selectRaw("journal_date AS d, SUM($kolom) AS v")->pluck('v', 'd')->all();

        // Transaksi tanpa detail
        foreach ($dibuat as $t) {
            if (in_array($t->type, ['normal', 'retur'], true) && $d($t, 'n') == 0) {
                $tambah($tanggal($t->created_at), 'penjualan', 'Transaksi tanpa detail', null, (float) $t->total_sum_price, 'ID transaksi ' . $t->id, (int) $t->id);
            }
        }

        // Penjualan tunai: satu jurnal per hari (Kas di Tangan / Penjualan, tanpa type_id).
        $bandingkan(
            $perTanggal($terjual->where('payment', 'cash'), $uang),
            $jurnalHarian(DB::table('journals')->where('type', 'transaction')->where('debit_account_id', $akun('1111'))
                ->where('credit_account_id', $akun('4101'))->whereNull('type_id'), 'debit'),
            fn ($tgl, $e, $j) => $tambah($tgl, 'penjualan', 'Penjualan tunai', $e, $j, 'selisih ' . $fmt($j - $e))
        );

        // Penjualan transfer: satu jurnal per transaksi (type_id = id transaksi).
        $transfer = $terjual->where('payment', 'transfer');
        $jurnalTransfer = [];
        foreach (array_chunk($transfer->pluck('id')->all(), self::CHUNK) as $ids) {
            $jurnalTransfer += DB::table('journals')->whereNull('deleted_at')->where('type', 'transaction')->whereIn('type_id', $ids)
                ->groupBy('type_id')->selectRaw('type_id AS t, SUM(debit) AS v')->pluck('v', 't')->all();
        }
        foreach ($transfer as $t) {
            $v = $uang($t);
            $j = (float) ($jurnalTransfer[$t->id] ?? 0);
            if (abs($v - $j) >= self::TOLERANSI) {
                $tambah($tanggal($t->created_at), 'penjualan', 'Penjualan transfer', $v, $j, 'ID transaksi ' . $t->id, (int) $t->id);
            }
        }

        // HPP penjualan: satu jurnal per hari (HPP / Persediaan, nama "Penjualan tanggal ...").
        $terbalik = $jurnalHarian(DB::table('journals')->where('type', 'hpp')->where('debit_account_id', $akun('1141'))
            ->where(fn ($q) => $q->where('name', 'like', 'Penjualan reverse%')->orWhere('name', 'like', 'Penjualan delete%')), 'debit');
        $bandingkan(
            $perTanggal($terjual->whereIn('payment', ['cash', 'transfer']), fn ($t) => $d($t, 'hpp')),
            $jurnalHarian(DB::table('journals')->where('type', 'hpp')->where('debit_account_id', $akun('5101'))
                ->where('credit_account_id', $akun('1141'))->where('name', 'like', 'Penjualan tanggal %'), 'credit'),
            fn ($tgl, $e, $j) => $tambah($tgl, 'hpp', 'HPP penjualan', $e, $j, $j == 0 && $e > 0 && isset($terbalik[$tgl])
                ? 'HPP hari ini kemungkinan tercatat di jurnal reverse (arah terbalik)' : 'selisih ' . $fmt($j - $e))
        );

        // Retur harus punya jurnal HPP retur.
        $retur = $dibuat->filter(fn ($t) => $t->type === 'retur' && $d($t, 'n_retur') > 0);
        $adaJurnal = self::punyaJurnalHpp($retur->pluck('id')->all(), [fn ($id) => "HPP retur barang (ID transaksi $id)"]);
        foreach ($retur as $t) {
            if (! isset($adaJurnal[$t->id])) {
                $tambah($tanggal($t->created_at), 'hpp', 'Retur tanpa jurnal HPP', $d($t, 'hpp_retur'), 0,
                    'ID transaksi ' . $t->id . ' (stok/loading retur perlu dicek)', (int) $t->id);
            }
        }

        // Transaksi yang dibatalkan di periode ini harus punya jurnal HPP balik.
        $batal = $trx->filter(fn ($t) => in_array($t->type, ['not valid', 'deleted'], true)
            && in_array($t->payment, ['cash', 'transfer'], true) && $dalam($t->updated_at))->sortBy('id');
        $adaJurnal = self::punyaJurnalHpp($batal->pluck('id')->all(), [
            fn ($id) => "Penjualan reverse transaction ID $id ", fn ($id) => "Penjualan delete transaction ID $id ",
        ]);
        foreach ($batal as $t) {
            if (! isset($adaJurnal[$t->id]) && $d($t, 'hpp_semua') > 0) {
                $tambah($tanggal($t->updated_at), 'hpp', 'Batal transaksi tanpa jurnal HPP', $d($t, 'hpp_semua'), 0, 'ID transaksi ' . $t->id, (int) $t->id);
            }
        }

        return $baris;
    }

    /**
     * Transaksi yang dibuat, diubah, atau dihapus di periode ini (termasuk yang
     * sudah dihapus). Satu kali baca tabel transactions.
     */
    private static function transaksiPeriode(string $from, string $to): Collection
    {
        $awal  = $from . ' 00:00:00';
        $akhir = date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00';
        $dalam = fn (string $kolom) => fn ($q) => $q->where($kolom, '>=', $awal)->where($kolom, '<', $akhir);

        return DB::table('transactions')
            ->where(fn ($q) => $q->where($dalam('created_at'))->orWhere($dalam('updated_at'))->orWhere($dalam('deleted_at')))
            ->orderBy('id')
            ->get(['id', 'type', 'payment', 'created_at', 'updated_at', 'deleted_at', 'total_sum_price', 'total_discount_price', 'voucher_nominal']);
    }

    /**
     * Ringkasan detail per transaksi:
     * n (detail aktif), jual (sum_price barang terjual), hpp (HPP barang terjual),
     * hpp_semua (HPP barang terjual termasuk detail yang sudah dihapus, untuk batal transaksi),
     * n_retur & hpp_retur (barang retur).
     */
    private static function ringkasDetail(array $trxIds): array
    {
        $hasil = [];
        foreach (array_chunk($trxIds, self::CHUNK) as $ids) {
            $rows = DB::table('transaction_details')->whereIn('transaction_id', $ids)->groupBy('transaction_id')
                ->selectRaw("transaction_id,
                    SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END) AS n,
                    SUM(CASE WHEN deleted_at IS NULL AND COALESCE(type, '') <> 'retur' THEN sum_price ELSE 0 END) AS jual,
                    SUM(CASE WHEN deleted_at IS NULL AND COALESCE(type, '') <> 'retur' THEN quantity * buy_price ELSE 0 END) AS hpp,
                    SUM(CASE WHEN COALESCE(type, '') <> 'retur' THEN quantity * buy_price ELSE 0 END) AS hpp_semua,
                    SUM(CASE WHEN deleted_at IS NULL AND type = 'retur' THEN 1 ELSE 0 END) AS n_retur,
                    SUM(CASE WHEN deleted_at IS NULL AND type = 'retur' THEN quantity * buy_price ELSE 0 END) AS hpp_retur")
                ->get();
            foreach ($rows as $r) {
                $hasil[$r->transaction_id] = $r;
            }
        }

        return $hasil;
    }

    /**
     * Transaksi yang punya jurnal HPP dengan nama berawalan salah satu pola.
     *
     * @param  list<callable(int): string>  $awalan
     * @return array<int, true>
     */
    private static function punyaJurnalHpp(array $trxIds, array $awalan): array
    {
        $hasil = [];
        foreach (array_chunk($trxIds, 100) as $ids) {
            $nama = DB::table('journals')->whereNull('deleted_at')->where('type', 'hpp')
                ->where(function ($q) use ($ids, $awalan) {
                    foreach ($ids as $id) {
                        foreach ($awalan as $a) {
                            $q->orWhere('name', 'like', $a((int) $id) . '%');
                        }
                    }
                })->pluck('name');

            foreach ($ids as $id) {
                foreach ($awalan as $a) {
                    if ($nama->contains(fn ($n) => stripos($n, $a((int) $id)) === 0)) {
                        $hasil[$id] = true;
                    }
                }
            }
        }

        return $hasil;
    }

    // ----------------------------------------------------------------- stok

    private static function cekStok(string $date, Collection $trx): array
    {
        $goodIds = self::barangBergerak($date, $trx);
        if (! $goodIds) {
            return self::hasil('stok', 'Stok', true, 'Tidak ada barang yang bergerak hari itu.');
        }

        $hitung = StockService::countersMany($goodIds);
        $goods = collect();
        foreach (array_chunk($goodIds, self::CHUNK) as $ids) {
            $goods = $goods->merge(DB::table('goods')->whereIn('id', $ids)->get(['id', 'name', 'type_id', 'last_stock', 'deleted_at']));
        }

        $beda  = $goods->filter(fn ($g) => round((float) $g->last_stock, 2) != $hitung[$g->id]['last_stock'])->sortBy('name')->values();
        $minus = $goods->filter(fn ($g) => $g->deleted_at === null && (float) $g->last_stock < 0)->sortBy('last_stock')->values();
        $angka = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');

        if ($beda->isEmpty() && $minus->isEmpty()) {
            return self::hasil('stok', 'Stok', true, 'Stok ' . count($goodIds) . ' barang yang bergerak hari itu cocok dengan riwayatnya, tidak ada yang minus.');
        }

        // Nama lengkap ("Jenis nama") hanya untuk yang ditampilkan.
        Good::applyFullNames($beda->merge($minus)->unique('id')->take(self::MAKS_RINCIAN * 2)->all());

        $rincian = [];
        foreach ($beda as $g) {
            $rincian[] = ['teks' => $g->name . ': stok tercatat ' . $angka($g->last_stock) . ', hitungan dari loading & transaksi '
                . $angka($hitung[$g->id]['last_stock']), 'url' => 'admin/good/' . $g->id . '/detail'];
        }
        // Akun yang membuat stoknya minus (StockMinusLog), kalau tercatat.
        $penyebab = StockMinusLog::terbukaUntuk($minus->take(self::MAKS_RINCIAN)->pluck('id')->all());
        foreach ($minus as $g) {
            $log = $penyebab[$g->id] ?? null;
            $rincian[] = ['teks' => $g->name . ': stok minus ' . $angka($g->last_stock) . ($log ? ', jadi minus: ' . $log->teks() : ''),
                'url' => 'admin/good/' . $g->id . '/detail'];
        }

        $pesan = [];
        if ($beda->isNotEmpty()) {
            $pesan[] = $beda->count() . ' barang stok tercatatnya beda dengan riwayat loading/transaksi';
        }
        if ($minus->isNotEmpty()) {
            $pesan[] = $minus->count() . ' barang stoknya minus (loading belum diinput?)';
        }

        return self::hasil('stok', 'Stok', false, ucfirst(implode('; ', $pesan)) . ', dari ' . count($goodIds) . ' barang yang bergerak hari itu.',
            null, $rincian, ['good_ids' => $beda->pluck('id')->map(fn ($id) => (int) $id)->all()]);
    }

    /** Barang yang punya transaksi atau loading yang dibuat/diubah/dihapus pada tanggal itu. */
    private static function barangBergerak(string $date, Collection $trx): array
    {
        $awal  = $date . ' 00:00:00';
        $akhir = date('Y-m-d', strtotime($date . ' +1 day')) . ' 00:00:00';
        $dalam = fn (string $kolom) => fn ($q) => $q->where($kolom, '>=', $awal)->where($kolom, '<', $akhir);

        $loadingIds = DB::table('good_loadings')
            ->where(fn ($q) => $q->where('loading_date', $date)->orWhere($dalam('created_at'))->orWhere($dalam('updated_at'))->orWhere($dalam('deleted_at')))
            ->pluck('id')->all();

        $goodIds = [];
        foreach (['transaction_details' => [$trx->pluck('id')->all(), 'transaction_id'], 'good_loading_details' => [$loadingIds, 'good_loading_id']] as $tabel => [$parentIds, $kolom]) {
            foreach (array_chunk($parentIds, self::CHUNK) as $ids) {
                $goodIds = array_merge($goodIds, DB::table($tabel)->join('good_units', 'good_units.id', '=', $tabel . '.good_unit_id')
                    ->whereIn($tabel . '.' . $kolom, $ids)->distinct()->pluck('good_units.good_id')->all());
            }
        }

        return array_values(array_unique(array_map('intval', $goodIds)));
    }

    // ------------------------------------------------------------- Kasir HP

    private static function cekKasirHp(): ?array
    {
        if (! PosDevice::tabelAda()) {
            return null;
        }

        $ditolak = PosSyncReject::whereNull('resolved_at')->with('device')->orderBy('id')->get();
        if ($ditolak->isEmpty()) {
            return self::hasil('kasir_hp', 'Kasir HP/PC', true, 'Tidak ada transaksi Kasir HP/PC yang ditolak saat sinkron.', 'admin/kasir-hp');
        }

        $rincian = $ditolak->map(fn (PosSyncReject $r) => [
            'teks' => ($r->device->name ?? 'Perangkat #' . $r->pos_device_id) . ', nota ' . ($r->receipt_no ?: '#' . $r->id)
                . ($r->created_at ? ' (' . $r->created_at->format('d/m/Y H:i') . ')' : '') . ': ' . Str::limit($r->reason, 150),
            'url' => 'admin/kasir-hp',
        ])->all();

        return self::hasil('kasir_hp', 'Kasir HP/PC', false,
            $ditolak->count() . ' transaksi Kasir HP/PC ditolak saat sinkron dan belum diselesaikan. Transaksinya belum masuk ke penjualan dan stok.',
            'admin/kasir-hp', $rincian);
    }
}
