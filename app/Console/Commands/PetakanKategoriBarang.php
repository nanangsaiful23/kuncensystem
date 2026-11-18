<?php

namespace App\Console\Commands;

use App\Services\PenataanKategori\PemetaanBarang;
use Illuminate\Console\Command;

/**
 * Tahap 3 penataan kategori: petakan barang ke kategori & jenis barang baru.
 *
 *   php artisan barang:petakan-kategori
 *       Dry-run (bawaan): tulis usulan ke storage/app/penataan-kategori/
 *       (pemetaan-*.csv dan tidak-terpetakan-*.csv). Database tidak diubah.
 *
 *   php artisan barang:petakan-kategori --terapkan=path/ke/pemetaan.csv --sudah-backup
 *       Terapkan CSV yang SUDAH DIPERIKSA. Wajib --sudah-backup (export database
 *       sudah dibuat). Sebelum menulis, otomatis dibuat tabel bak_tahap3_goods_*.
 *
 *   php artisan barang:petakan-kategori --pulihkan=bak_tahap3_goods_20261118_120000
 *       Kembalikan nama/jenis/kategori barang dari tabel backup itu.
 *
 * Di server tanpa terminal, pakai halaman admin "Penataan Kategori".
 */
class PetakanKategoriBarang extends Command
{
    protected $signature = 'barang:petakan-kategori
                            {--terapkan= : CSV pemetaan yang sudah diperiksa}
                            {--sudah-backup : Konfirmasi export database sudah dibuat}
                            {--pulihkan= : Nama tabel backup bak_tahap3_goods_*}';

    protected $description = 'Petakan barang ke kategori dan jenis barang baru (bawaan: dry-run ke CSV)';

    public function handle(): int
    {
        $svc = new PemetaanBarang();

        foreach ($svc->ruleErrors() as $err) {
            $this->warn($err);
        }

        if ($table = $this->option('pulihkan')) {
            $n = $svc->pulihkan($table);
            $this->info("$n barang dikembalikan dari $table.");

            return self::SUCCESS;
        }

        if ($path = $this->option('terapkan')) {
            if (! $this->option('sudah-backup')) {
                $this->error('Buat export database dulu, lalu ulangi dengan --sudah-backup.');

                return self::FAILURE;
            }
            if (! is_file($path)) {
                $this->error("File $path tidak ditemukan.");

                return self::FAILURE;
            }

            $r = $svc->terapkanCsv($path);
            $this->info("Backup sebelum penerapan: {$r['backup']}");
            $this->info("{$r['diubah']} barang diubah ({$r['nama_diubah']} namanya diubah).");
            if ($r['dilewati']) {
                $this->warn(count($r['dilewati']) . ' baris dilewati:');
                foreach (array_slice($r['dilewati'], 0, 50) as $s) {
                    $this->line('  - ' . $s);
                }
            }

            return self::SUCCESS;
        }

        // Dry-run
        $u = $svc->usulan();
        $dir = storage_path('app/penataan-kategori');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $stamp = date('Ymd-His');
        $fMap = "$dir/pemetaan-$stamp.csv";
        $fUn = "$dir/tidak-terpetakan-$stamp.csv";
        PemetaanBarang::tulisCsv($fMap, PemetaanBarang::KOLOM_PEMETAAN, $u['pemetaan']);
        PemetaanBarang::tulisCsv($fUn, PemetaanBarang::KOLOM_TIDAK_TERPETAKAN, $u['tidak_terpetakan']);

        $this->table(['Kategori lama', 'Barang', 'Berstok', 'Berubah', 'Tidak terpetakan', 'Tetap'],
            array_map(fn ($s) => array_values($s), $u['ringkasan']));
        $this->info('DRY-RUN, database tidak diubah.');
        $this->info('Usulan: ' . $fMap . ' (' . count($u['pemetaan']) . ' baris)');
        $this->info('Tidak terpetakan: ' . $fUn . ' (' . count($u['tidak_terpetakan']) . ' baris)');

        return self::SUCCESS;
    }
}
