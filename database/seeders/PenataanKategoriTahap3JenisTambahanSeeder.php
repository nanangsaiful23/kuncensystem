<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Tahap 3a penataan kategori: 24 jenis barang tambahan yang disetujui pemilik
 * (Cetakan & Loyang, Nampan, Rak Plastik, dst.). Isinya sama dengan file SQL
 * untuk phpMyAdmin:
 *   docs/sql/2026-11-18-tahap3a-jenis-tambahan.sql        (jalankan)
 *   docs/sql/2026-11-18-tahap3a-jenis-tambahan-BATAL.sql  (batalkan)
 *
 * Jalankan:
 *   php artisan db:seed --class=PenataanKategoriTahap3JenisTambahanSeeder
 *
 * Aman dijalankan ulang.
 */
class PenataanKategoriTahap3JenisTambahanSeeder extends Seeder
{
    public function run(): void
    {
        $sql = file_get_contents(base_path('docs/sql/2026-11-18-tahap3a-jenis-tambahan.sql'));

        // Hanya perintah INSERT; query cek di akhir file untuk phpMyAdmin.
        foreach (preg_split('/;\s*\r?\n/', $sql) as $statement) {
            $statement = trim(preg_replace('/^--.*$/m', '', $statement));
            if (stripos($statement, 'INSERT') === 0) {
                DB::statement($statement);
            }
        }
    }
}
