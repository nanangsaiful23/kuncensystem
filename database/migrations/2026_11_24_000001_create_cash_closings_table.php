<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tutup Kasir harian, lihat docs/rencana-optimasi-dan-tutup-kasir.md poin 4.
 *
 * cash_closings: satu baris per tanggal (yang belum dibatalkan). Menyimpan
 * saldo Kas di Tangan menurut sistem, uang fisik hasil hitungan, selisihnya,
 * jumlah yang diserahkan ke owner, dan jurnal yang dibuat.
 *
 * Jalankan:
 *   php artisan migrate --path=database/migrations/2026_11_24_000001_create_cash_closings_table.php
 * (pengganti tanpa artisan: docs/sql/2026-11-24-tutup-kasir.sql, atau cukup
 *  buka halaman Tutup Kasir: tabel dibuat otomatis)
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('cash_closings')) {
            return;
        }

        Schema::create('cash_closings', function (Blueprint $table) {
            $table->increments('id');
            $table->date('closing_date');
            $table->decimal('system_balance', 14, 2);
            $table->decimal('physical_cash', 14, 2);
            $table->decimal('difference', 14, 2);
            $table->decimal('draw_amount', 14, 2)->default(0);
            $table->unsignedBigInteger('adjustment_journal_id')->nullable();
            $table->unsignedBigInteger('draw_journal_id')->nullable();
            $table->text('note')->nullable();
            $table->string('role', 20);
            $table->unsignedInteger('role_id');
            $table->timestamps();
            $table->softDeletes();

            $table->index('closing_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cash_closings');
    }
};
