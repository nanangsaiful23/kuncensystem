<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tugas cek stok (Sidak berisi daftar) dan harga obral.
 *
 * stock_inspection_targets : daftar barang yang harus dicek pada satu sesi
 *   sidak (dari centang di Perputaran Barang, atau "Sidak dead stock").
 *   position = nomor urut, sama dengan nomor di teks WA untuk pegawai.
 * stock_inspections.counted_by : nama yang menghitung fisik, kalau bukan
 *   orang yang menginput (pegawai menghitung, admin mengetik dari balasan WA).
 *   Ikut jadi "pemeriksa" di Stock Opname hasil sidak.
 * good_units.clearance_normal_price : harga jual sebelum obral. Dipakai untuk
 *   harga coret di label & teks WA, dan untuk mengembalikan harga saat obral
 *   dibatalkan atau selesai. NULL = satuan ini tidak sedang diobral.
 *
 * Aman dijalankan ulang.
 *
 * Jalankan:
 *   php artisan migrate --path=database/migrations/2026_11_28_000001_add_sidak_targets_and_clearance_price.php
 * (pengganti tanpa artisan: docs/sql/2026-11-28-tugas-cek-stok-dan-harga-obral.sql, atau cukup
 *  buka menu Perputaran Barang / Sidak Stok: kolom & tabel dibuat otomatis)
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('stock_inspections') && ! Schema::hasColumn('stock_inspections', 'counted_by')) {
            Schema::table('stock_inspections', function (Blueprint $table) {
                $table->string('counted_by', 100)->nullable()->after('note');
            });
        }

        if (! Schema::hasTable('stock_inspection_targets')) {
            Schema::create('stock_inspection_targets', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('stock_inspection_id');
                $table->unsignedBigInteger('good_id');
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();

                $table->unique(['stock_inspection_id', 'good_id'], 'uq_sit_inspection_good');
            });
        }

        if (! Schema::hasColumn('good_units', 'clearance_normal_price')) {
            Schema::table('good_units', function (Blueprint $table) {
                $table->decimal('clearance_normal_price', 12, 2)->nullable()->after('selling_price');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('good_units', 'clearance_normal_price')) {
            Schema::table('good_units', fn (Blueprint $table) => $table->dropColumn('clearance_normal_price'));
        }
        Schema::dropIfExists('stock_inspection_targets');
        if (Schema::hasTable('stock_inspections') && Schema::hasColumn('stock_inspections', 'counted_by')) {
            Schema::table('stock_inspections', fn (Blueprint $table) => $table->dropColumn('counted_by'));
        }
    }
};
