<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Struktur 3 tingkat: Area > Kategori > Jenis Barang.
 *
 * - Area memakai tabel main_categories yang sudah ada (dulu "kategori utama").
 *   Hubungannya pindah dari tabel penghubung sub_categories ke kolom
 *   categories.main_category_id (satu kategori = satu area). Isinya disalin
 *   dari sub_categories; tabel sub_categories dibiarkan, tidak dihapus.
 * - categories.habis_pakai : 1 = barang habis pakai, 0 = alat/barang tahan lama.
 * - types.category_id      : kategori induk jenis barang.
 * - types.tipe_kontrol, min_model, max_model : untuk laporan kontrol model.
 *
 * Status aktif/nonaktif barang tetap memakai goods.is_discontinued.
 *
 * Jalankan:
 *   php artisan migrate --path=database/migrations/2026_11_18_000001_add_area_and_model_control_to_categories_and_types.php
 * (pengganti tanpa artisan: docs/sql/2026-11-18-tahap1-struktur-kategori.sql)
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->bigInteger('main_category_id')->nullable()->after('id');
            $table->tinyInteger('habis_pakai')->default(0)->after('color');

            $table->index('main_category_id', 'idx_categories_main_category');
        });

        // Salin area dari tabel penghubung lama.
        DB::update('UPDATE categories c
                    JOIN sub_categories s ON s.category_id = c.id AND s.deleted_at IS NULL
                    SET c.main_category_id = s.main_category_id');

        Schema::table('types', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->after('id');
            $table->enum('tipe_kontrol', ['fungsional', 'pilihan_desain', 'musiman'])->nullable()->after('eng_name');
            $table->unsignedSmallInteger('min_model')->nullable()->after('tipe_kontrol');
            $table->unsignedSmallInteger('max_model')->nullable()->after('min_model');

            $table->index('category_id', 'idx_types_category');
        });

        // Samakan collation dengan tabel goods/categories (utf8mb4_unicode_ci)
        // supaya query yang membandingkan nama antar tabel tidak error.
        DB::statement('ALTER TABLE types CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        DB::statement('ALTER TABLE main_categories CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    }

    public function down()
    {
        DB::statement('ALTER TABLE main_categories CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        DB::statement('ALTER TABLE types CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');

        Schema::table('types', function (Blueprint $table) {
            $table->dropIndex('idx_types_category');
            $table->dropColumn(['category_id', 'tipe_kontrol', 'min_model', 'max_model']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_categories_main_category');
            $table->dropColumn(['main_category_id', 'habis_pakai']);
        });
    }
};
