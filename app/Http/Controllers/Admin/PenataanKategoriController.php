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
            abort_unless(isSupervisor(), 403, 'Penataan kategori hanya untuk supervisor.');

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
