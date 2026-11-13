<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sidak stok toko: supervisor datang ke toko, memilih barang secara acak
 * (atau scan barcode) lewat HP, lalu mencatat jumlah fisik di rak.
 *
 * Sidak HANYA mencatat selisih antara stok sistem dan stok fisik. Stok,
 * jurnal dan transaksi tidak diubah; koreksi stok tetap lewat Stock Opname.
 *
 * Jalankan:
 *   php artisan migrate --path=database/migrations/2026_11_13_000002_create_stock_inspections_table.php
 * (pengganti tanpa artisan: docs/sql/2026-11-13-tambah-sidak-stok.sql)
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('stock_inspections')) {
            Schema::create('stock_inspections', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('role', 20)->nullable();
                $table->unsignedBigInteger('role_id')->nullable();
                $table->string('note')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('created_at', 'idx_si_created');
            });
        }

        if (! Schema::hasTable('stock_inspection_details')) {
            Schema::create('stock_inspection_details', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('stock_inspection_id');
                $table->unsignedBigInteger('good_id');
                $table->unsignedBigInteger('good_unit_id')->nullable();
                $table->string('unit_name', 50)->nullable();
                // stok sistem saat dihitung & stok fisik, dalam satuan terkecil
                $table->decimal('system_stock', 14, 4)->default(0);
                $table->decimal('real_stock', 14, 4)->default(0);
                $table->decimal('difference', 14, 4)->default(0);   // fisik - sistem
                $table->decimal('buy_price', 14, 2)->default(0);
                $table->string('source', 10)->default('scan');     // scan | acak | cari
                $table->string('note')->nullable();
                $table->timestamps();

                $table->unique(['stock_inspection_id', 'good_id'], 'uq_sid_inspection_good');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('stock_inspection_details');
        Schema::dropIfExists('stock_inspections');
    }
};
