<?php

namespace App\Http\Controllers\Base;

use App\Models\CashClosing;
use App\Models\GoodLoading;
use App\Models\ReconciliationCheck;
use App\Services\CekKecocokan;
use App\Services\TutupKasir;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Tutup Kasir untuk admin dan kasir (lihat App\Services\TutupKasir).
 * Tampil di halaman Resume Transaksi Total: tanggal halaman itu = tanggal tutup kasir.
 */
trait CashClosingControllerBase
{
    /** Data kotak Tutup Kasir di Resume Transaksi Total ($tk di view). */
    public function tutupKasirViewData(string $role, string $start_date, string $end_date): array
    {
        // Dibuat di GET (bukan di POST): CREATE TABLE di MySQL menutup
        // transaksi yang dibuka AtomicRequest untuk POST.
        $tk = ['tabelAda' => CashClosing::pastikanTabel(), 'boleh' => canMenu('kas.tutup')];
        if (! $tk['tabelAda'] || ! $tk['boleh']) {
            return $tk;
        }
        // Hasil cek kecocokan yang dijalankan saat tutup kasir disimpan.
        $tk['cekAda'] = ReconciliationCheck::pastikanTabel();
        // Laci asal uang loading barang yang dibayar dari Kas di Tangan.
        GoodLoading::pastikanKolomLaci();
        $tk['role'] = $role;

        $last = TutupKasir::last();
        $tk['last']       = $last;
        $tk['bolehBatal'] = $role === 'admin' && canMenu('aksi.tutup_kasir.batal');
        $tk['tanggal']    = $start_date === $end_date ? $start_date : null;
        $tk['closings']   = CashClosing::whereBetween('closing_date', [min($start_date, $end_date), max($start_date, $end_date)])
            ->orderByDesc('closing_date')->orderByDesc('id')->get();

        if ($tk['tanggal'] !== null) {
            $date = $tk['tanggal'];
            $tk['closing'] = TutupKasir::forDate($date);

            if ($tk['closing'] !== null) {
                $tk['status'] = 'closed';
                $tk['cek'] = $tk['cekAda'] ? ReconciliationCheck::where('cash_closing_id', $tk['closing']->id)->latest('id')->first() : null;
                // Uang masuk/keluar per laci dihitung ulang dari data sekarang (mis. laci loading baru dipilih).
                $panduan = TutupKasir::panduanPerKasir($date); // sekali saja: tabel transaksi tidak berindeks tanggal
                $tk['laciTampil'] = TutupKasir::laciTutupKasir($tk['closing'], $panduan);
                $tk['cekLaci'] = null;
                $tk['saldoSekarang'] = null;
                if ($tk['laciTampil'] && ! in_array(null, array_column($tk['laciTampil'], 'key'), true)) {
                    $saldo = TutupKasir::saldoSebelumTutup($tk['closing']);
                    $tk['cekLaci'] = TutupKasir::cekJumlahLaci($date, $tk['laciTampil'], $saldo, $tk['closing'], $panduan);
                    $tk['saldoSekarang'] = abs($saldo - $tk['closing']->system_balance) >= 0.01 ? $saldo : null;
                }
            } elseif ($date > date('Y-m-d')) {
                $tk['status'] = 'future';
            } elseif ($last !== null && $last->closing_date->format('Y-m-d') > $date) {
                $tk['status'] = 'blocked';
            } else {
                $tk['status']  = 'open';
                $tk['saldo']   = TutupKasir::saldoKasDiTangan($date);
                $tk['lampau']  = $date < date('Y-m-d');
                $tk['devices'] = TutupKasir::devices();
                $panduan = TutupKasir::panduanPerKasir($date); // sekali saja: tabel transaksi tidak berindeks tanggal
                $tk['laci']    = TutupKasir::daftarLaci($date, $panduan);
                // Jumlah perkiraan semua laci harus = saldo sistem; yang tidak masuk laci didaftar.
                $tk['cekLaci'] = TutupKasir::cekJumlahLaci($date, $tk['laci'], $tk['saldo'], null, $panduan);
            }
        }

        return $tk;
    }

    public function storeBase(Request $request, string $role)
    {
        $request->validate([
            'closing_date'  => 'required|date_format:Y-m-d',
            // Per laci (form Resume Total): laci_kasir[] (kunci dari TutupKasir::daftarLaci(), mis. "cashier:15";
            // kosong = laci tambahan admin), laci_nama[] (hanya dipakai laci tambahan), laci_uang[], laci_tarik[].
            'laci_uang'     => 'nullable|array|max:20',
            'laci_uang.*'   => 'nullable|string|max:30',
            'laci_tarik'    => 'nullable|array|max:20',
            'laci_tarik.*'  => 'nullable|string|max:30',
            'laci_nama'     => 'nullable|array|max:20',
            'laci_nama.*'   => 'nullable|string|max:60',
            'laci_kasir'    => 'nullable|array|max:20',
            'laci_kasir.*'  => 'nullable|string|max:100',
            // Tanpa rincian laci: satu angka total uang fisik dan penarikan.
            'physical_cash' => 'nullable|string|max:30',
            'draw_amount'   => 'nullable|string|max:30',
            'note'          => 'nullable|string|max:1000',
        ]);

        if (! CashClosing::tabelAda()) {
            throw ValidationException::withMessages(['physical_cash' => 'Tabel tutup kasir belum ada. Buka ulang halaman Resume Transaksi Total.']);
        }

        $date = $request->closing_date;
        $laci = $this->laciDariForm($request, $date, $role);
        $fisik = $laci ? array_sum(array_column($laci, 'uang')) : parseNumber($request->physical_cash);
        if ($fisik === null) {
            throw ValidationException::withMessages(['physical_cash' => 'Isi uang fisik minimal satu laci.']);
        }
        // Penarikan per laci dijumlah: satu jurnal Kas di Tangan -> Kas di Nanang.
        $diserahkan = $laci ? array_sum(array_column($laci, 'tarik')) : (parseNumber($request->draw_amount) ?? 0.0);
        $saldoDilihat = is_numeric($request->system_balance_seen) ? (float) $request->system_balance_seen : null;

        $closing = TutupKasir::close($fisik, $diserahkan, $request->note, $role, \Auth::user()->id, $saldoDilihat, $date, $laci);
        // Cek kecocokan harian; yang tidak cocok tampil di dashboard admin.
        $cek = CekKecocokan::setelahTutupKasir($closing);

        if ($closing->difference == 0) {
            $selisih = 'uang pas';
        } elseif ($closing->difference < 0) {
            $selisih = 'uang kurang ' . showRupiah(abs($closing->difference)) . ' (dicatat ke Biaya Uang Hilang)';
        } else {
            $selisih = 'uang lebih ' . showRupiah($closing->difference) . ' (dicatat ke Pendapatan Lain-lain)';
        }

        return redirect(self::resumeTotalUrl($role, $date))->with('tutup_kasir_ok',
            'Tutup kasir ' . displayDate($date) . ' tersimpan: ' . $selisih . '. Diserahkan ke owner ' . showRupiah($closing->draw_amount)
            . ', sisa di laci ' . showRupiah($closing->remaining()) . '.' . self::ringkasCek($cek, $role));
    }

    /** Satu kalimat hasil cek kecocokan untuk pesan setelah simpan. */
    private static function ringkasCek(?ReconciliationCheck $cek, string $role): string
    {
        if ($cek === null) {
            return '';
        }
        if ($cek->problem_count === 0) {
            return ' Cek kecocokan: semua cocok.';
        }

        return ' Cek kecocokan: ' . $cek->problem_count . ' tidak cocok (' . implode(', ', array_column($cek->masalah(), 'label')) . '), '
            . ($role === 'admin' ? 'rinciannya di bawah dan di dashboard admin.' : 'sudah dilaporkan ke admin.');
    }

    /**
     * Baris laci dari form, dicocokkan dengan TutupKasir::daftarLaci(): nama,
     * sisa kemarin, dan panduan diambil dari server (bukan dari form). Laci dari
     * daftar wajib diisi uangnya (0 kalau kosong); penarikan boleh kosong (= 0).
     * Hanya admin yang boleh menambah laci (kunci kosong, nama bebas).
     */
    private function laciDariForm(Request $request, string $date, string $role): array
    {
        $uang  = (array) $request->input('laci_uang', []);
        $tarik = (array) $request->input('laci_tarik', []);
        $nama  = (array) $request->input('laci_nama', []);
        $kasir = (array) $request->input('laci_kasir', []);
        if (! $uang) {
            return [];
        }
        $daftar = collect(TutupKasir::daftarLaci($date))->keyBy('key');

        $laci = [];
        $belumDiisi = [];
        $dikirim = [];
        foreach ($uang as $i => $nilai) {
            $key = (string) ($kasir[$i] ?? '');
            $d = $daftar[$key] ?? null;
            $dikirim[$key] = true;

            if ($d === null && $key !== '') {
                throw ValidationException::withMessages(['physical_cash' =>
                    'Daftar laci berubah sejak halaman dibuka (ada transaksi baru atau dihapus). Buka ulang halaman, cek lagi, lalu simpan.']);
            }
            if ($d === null) {
                if ($role !== 'admin') {
                    throw ValidationException::withMessages(['physical_cash' => 'Kasir tidak bisa menambah laci.']);
                }
                $namaLaci = substr(trim((string) ($nama[$i] ?? '')), 0, 60) ?: 'Laci tambahan ' . ($i + 1);
                $d = ['key' => 'tambahan:' . $namaLaci, 'nama' => $namaLaci, 'sisa' => null, 'p' => null, 'perkiraan' => null];
            }

            $angka = parseNumber($nilai);
            if ($angka === null) {
                if ($key !== '') {
                    $belumDiisi[] = $d['nama'];
                }
                continue;
            }
            $p = $d['p'];
            $laci[] = [
                'key'            => $d['key'],
                'nama'           => $d['nama'],
                'uang'           => round($angka, 2),
                'tarik'          => round(parseNumber($tarik[$i] ?? null) ?? 0.0, 2),
                'sisa_kemarin'   => $d['sisa'],
                'perkiraan'      => $d['perkiraan'],
                'panduan'        => $p['total'] ?? null,
                'penjualan_cash' => $p['penjualan'] ?? null,
                'lain_cash'      => $p['lain'] ?? null,
                'biaya_cash'     => $p['biaya'] ?? null,
                // Loading barang yang dibayar dari laci ini (sudah dikurangkan dari panduan & perkiraan).
                'loading_cash'   => $p['loading'] ?? null,
            ];
        }

        // Laci dari daftar yang tidak ikut terkirim sama sekali juga wajib diisi.
        foreach ($daftar as $key => $d) {
            if (! isset($dikirim[$key])) {
                $belumDiisi[] = $d['nama'];
            }
        }
        if ($belumDiisi) {
            throw ValidationException::withMessages(['physical_cash' =>
                'Isi uang fisik laci ' . implode(', ', $belumDiisi) . ' (isi 0 kalau lacinya kosong).']);
        }

        return $laci;
    }

    public static function resumeTotalUrl(string $role, string $date): string
    {
        return url($role . '/transaction/resumeTotal/' . $date . '/' . $date);
    }
}
