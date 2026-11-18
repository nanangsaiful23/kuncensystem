<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tahap 2 penataan kategori: isi Area, Kategori, dan Jenis Barang baru serta
 * gabungkan jenis barang yang dobel.
 *
 * Isinya sama persis dengan file SQL untuk phpMyAdmin, supaya hasil di lokal
 * dan di server tidak berbeda:
 *   docs/sql/2026-11-18-tahap2-isi-kategori-jenis.sql        (jalankan)
 *   docs/sql/2026-11-18-tahap2-isi-kategori-jenis-cek.sql    (periksa hasil)
 *   docs/sql/2026-11-18-tahap2-isi-kategori-jenis-BATAL.sql  (batalkan)
 *
 * Jalankan:
 *   php artisan db:seed --class=PenataanKategoriTahap2Seeder
 *
 * Syarat: migrasi 2026_11_18_000001 (Tahap 1) sudah dijalankan.
 * Aman dijalankan ulang; perubahan tidak dobel.
 */
class PenataanKategoriTahap2Seeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasColumn('types', 'category_id')) {
            throw new \RuntimeException('Jalankan migrasi Tahap 1 (2026_11_18_000001) lebih dulu.');
        }

        DB::unprepared(file_get_contents(base_path('docs/sql/2026-11-18-tahap2-isi-kategori-jenis.sql')));
    }
}
