<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\PenataanKategori\PemetaanBarang;

/**
 * Penataan kategori Tahap 3 lewat browser (server tanpa terminal).
 * Sama dengan command barang:petakan-kategori:
 *   1. unduh usulan pemetaan (CSV) dan daftar tidak terpetakan (CSV)
 *   2. pemilik memeriksa / mengoreksi CSV di Excel
 *   3. unggah CSV yang sudah diperiksa -> diterapkan (backup tabel otomatis)
 *   4. bila perlu, pulihkan dari tabel backup
 */
class PenataanKategoriController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(canMenu('kategori.penataan'), 403, 'Menu Penataan Kategori ditutup untuk akun Anda.');

            return $next($request);
        });
    }

    public function index()
    {
        $svc     = new PemetaanBarang();
        $usulan  = $svc->usulan();
        $errors  = $svc->ruleErrors();
        $backups = PemetaanBarang::daftarBackup();

        return view('admin.penataan-kategori.index', [
            'ringkasan'        => $usulan['ringkasan'],
            'jumlahPemetaan'   => count($usulan['pemetaan']),
            'jumlahTidak'      => count($usulan['tidak_terpetakan']),
            'ruleErrors'       => $errors,
            'backups'          => $backups,
        ]);
    }

    public function unduhPemetaan()
    {
        return $this->unduh('pemetaan', PemetaanBarang::KOLOM_PEMETAAN, 'pemetaan');
    }

    public function unduhTidakTerpetakan()
    {
        return $this->unduh('tidak_terpetakan', PemetaanBarang::KOLOM_TIDAK_TERPETAKAN, 'tidak-terpetakan');
    }

    public function terapkan(Request $request)
    {
        $request->validate([
            'file'         => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'sudah_backup' => ['accepted'],
        ], [
            'sudah_backup.accepted' => 'Centang konfirmasi bahwa export database sudah dibuat.',
            'file.required'         => 'Pilih file CSV pemetaan yang sudah diperiksa.',
        ]);

        try {
            $hasil = (new PemetaanBarang())->terapkanCsv($request->file('file')->getRealPath());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        return redirect()->route('admin.penataan-kategori.index')->with('hasil', $hasil);
    }

    /**
     * Petakan jenis dengan klik: daftar barang aktif (tidak terpetakan atau
     * yang punya usulan), dicari per kata, dicentang, lalu diberi jenis.
     */
    public function klik(Request $request)
    {
        $svc    = new PemetaanBarang();
        $usulan = $svc->usulan();
        $mode   = $request->get('mode') === 'usulan' ? 'usulan' : 'tidak';
        $kata   = trim((string) $request->get('kata', ''));
        $kat    = (string) $request->get('kategori', '');
        $stok   = $request->boolean('berstok');

        $rows = collect($mode === 'usulan' ? $usulan['pemetaan'] : $usulan['tidak_terpetakan'])
            ->where('status', 'aktif');
        $kategoriLama = $rows->pluck('kategori lama', 'kategori_lama_id')->sort();

        if ($kat !== '') {
            $rows = $rows->filter(fn ($r) => (string) $r['kategori_lama_id'] === $kat);
        }
        if ($stok) {
            $rows = $rows->filter(fn ($r) => (float) str_replace(',', '.', $r['stok']) > 0);
        }
        if ($kata !== '') {
            $words = preg_split('/\s+/u', mb_strtolower($kata));
            $rows  = $rows->filter(function ($r) use ($words) {
                $nama = mb_strtolower((string) $r['nama']);
                foreach ($words as $w) {
                    if (mb_strpos($nama, $w) === false) {
                        return false;
                    }
                }
                return true;
            });
        }
        // Urut nama supaya barang yang mirip berdekatan dan bisa dicentang sekaligus.
        $rows = $rows->sortBy(fn ($r) => mb_strtolower((string) $r['nama']))->values();

        $perHal = 150;
        $hal    = max(1, (int) $request->get('hal', 1));

        return view('admin.penataan-kategori.klik', [
            'mode'         => $mode,
            'kata'         => $kata,
            'kategori'     => $kat,
            'berstok'      => $stok,
            'kategoriLama' => $kategoriLama,
            'jenis'        => $svc->daftarJenis(),
            'total'        => $rows->count(),
            'rows'         => $rows->slice(($hal - 1) * $perHal, $perHal)->values(),
            'hal'          => $hal,
            'jumlahHal'    => max(1, (int) ceil($rows->count() / $perHal)),
            'jumlahTidak'  => collect($usulan['tidak_terpetakan'])->where('status', 'aktif')->count(),
            'jumlahUsulan' => collect($usulan['pemetaan'])->where('status', 'aktif')->count(),
        ]);
    }

    public function simpanKlik(Request $request)
    {
        // Hanya baris yang dicentang, dikirim sebagai JSON (hindari batas max_input_vars).
        $input = json_decode((string) $request->input('data', '[]'), true);
        $buang = $request->boolean('buang_awalan');

        $svc  = new PemetaanBarang();
        $rows = [];
        foreach (is_array($input) ? $input : [] as $r) {
            $id    = (int) ($r['id'] ?? 0);
            $jenis = trim((string) ($r['jenis'] ?? ''));
            if (! $id || $jenis === '') {
                continue;
            }
            $namaBaru = '';
            if ($buang && mb_strtolower($jenis) !== mb_strtolower((string) ($r['jenis_lama'] ?? ''))) {
                $namaBaru = $svc->buangAwalan((string) $r['nama'], $jenis);
            }
            $rows[] = [
                'id'               => $id,
                'nama'             => (string) ($r['nama'] ?? ''),
                'jenis baru'       => $jenis,
                'nama baru'        => $namaBaru,
                'kategori_lama_id' => (string) ($r['kategori_lama_id'] ?? ''),
                'jenis_lama_id'    => (string) ($r['jenis_lama_id'] ?? ''),
            ];
        }

        if (! $rows) {
            return back()->withErrors(['pilih' => 'Belum ada barang yang dicentang dan diberi jenis.']);
        }

        $hasil = $svc->terapkanBaris($rows, $svc->backupHarian());

        return back()->with('hasil', $hasil);
    }

    public function pulihkan(Request $request)
    {
        $request->validate([
            'backup'        => ['required', 'string', 'in:' . implode(',', PemetaanBarang::daftarBackup())],
            'yakin_pulihkan' => ['accepted'],
        ], [
            'yakin_pulihkan.accepted' => 'Centang konfirmasi pemulihan.',
        ]);

        $n = (new PemetaanBarang())->pulihkan($request->backup);

        return redirect()->route('admin.penataan-kategori.index')
            ->with('pesan', "$n barang dikembalikan ke kondisi di {$request->backup}.");
    }

    private function unduh(string $key, array $header, string $nama)
    {
        $rows = (new PemetaanBarang())->usulan()[$key];
        $path = tempnam(sys_get_temp_dir(), 'pk');
        PemetaanBarang::tulisCsv($path, $header, $rows);

        return response()->download($path, $nama . '-' . date('Ymd-His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend(true);
    }
}
