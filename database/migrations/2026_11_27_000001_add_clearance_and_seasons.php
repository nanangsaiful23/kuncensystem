<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Obral dan musim untuk menu Perputaran Barang & Rekomendasi Reorder.
 *
 * goods.is_clearance ... clearance_stock : barang sedang diobral (dihabiskan).
 *   Tidak dinilai fast/slow/dead dan tidak pernah disarankan order. Begitu
 *   stoknya habis, otomatis jadi discontinued ("Habis obral").
 *
 * seasons + season_periods : daftar musim (Ramadan–Lebaran, Tahun Ajaran Baru,
 *   Natal–Tahun Baru, Musim Hujan, ...) dengan tanggalnya. Musim berulang
 *   (repeats_yearly = 1) cukup satu periode; tanggal/bulannya dipakai setiap
 *   tahun. Musim yang tanggalnya bergeser (Ramadan) diisi periodenya per tahun.
 *
 * types.season_id : musim untuk seluruh barang di jenis itu.
 * goods.season_id : pengecualian per barang. NULL = ikut jenisnya,
 *   0 = bukan musiman (walaupun jenisnya musiman), > 0 = musim itu.
 *
 * Aman dijalankan ulang.
 *
 * Jalankan:
 *   php artisan migrate --path=database/migrations/2026_11_27_000001_add_clearance_and_seasons.php
 * (pengganti tanpa artisan: docs/sql/2026-11-27-obral-dan-musim.sql, atau cukup
 *  buka menu Perputaran Barang: kolom & tabel dibuat otomatis)
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('goods', 'is_clearance')) {
            Schema::table('goods', function (Blueprint $table) {
                $table->tinyInteger('is_clearance')->default(0)->after('discontinued_by');
                $table->timestamp('clearance_at')->nullable()->after('is_clearance');
                $table->text('clearance_reason')->nullable()->after('clearance_at');
                $table->unsignedBigInteger('clearance_by')->nullable()->after('clearance_reason');
                $table->decimal('clearance_stock', 12, 2)->nullable()->after('clearance_by');

                $table->index('is_clearance', 'idx_goods_clearance');
            });
        }

        if (! Schema::hasColumn('goods', 'season_id')) {
            Schema::table('goods', function (Blueprint $table) {
                $table->unsignedBigInteger('season_id')->nullable()->after('clearance_stock');
            });
        }

        if (! Schema::hasColumn('types', 'season_id')) {
            Schema::table('types', function (Blueprint $table) {
                $table->unsignedBigInteger('season_id')->nullable()->after('max_model');
            });
        }

        if (! Schema::hasTable('seasons')) {
            Schema::create('seasons', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 100);
                $table->tinyInteger('repeats_yearly')->default(0);
                $table->unsignedSmallInteger('prep_days')->default(30);
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('season_periods')) {
            Schema::create('season_periods', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('season_id');
                $table->date('start_date');
                $table->date('end_date');
                $table->timestamps();

                $table->index('season_id', 'idx_season_periods_season');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('season_periods');
        Schema::dropIfExists('seasons');

        if (Schema::hasColumn('types', 'season_id')) {
            Schema::table('types', fn (Blueprint $table) => $table->dropColumn('season_id'));
        }
        if (Schema::hasColumn('goods', 'season_id')) {
            Schema::table('goods', fn (Blueprint $table) => $table->dropColumn('season_id'));
        }
        if (Schema::hasColumn('goods', 'is_clearance')) {
            Schema::table('goods', function (Blueprint $table) {
                $table->dropIndex('idx_goods_clearance');
                $table->dropColumn(['is_clearance', 'clearance_at', 'clearance_reason', 'clearance_by', 'clearance_stock']);
            });
        }
    }
};
