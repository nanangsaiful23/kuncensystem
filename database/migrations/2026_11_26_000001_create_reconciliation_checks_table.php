<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cek kecocokan harian, lihat docs/rencana-optimasi-dan-tutup-kasir.md poin 3.
 *
 * reconciliation_checks: hasil cek yang dijalankan setiap kali Tutup Kasir
 * disimpan (satu baris per tutup kasir). results (JSON) berisi semua cek,
 * cocok atau tidak. problem_count = jumlah cek yang tidak cocok; kalau lebih
 * dari 0 hasilnya tampil di dashboard admin sampai resolved_at diisi
 * (ditandai "sudah dibaca" / "sudah ditangani").
 *
 * Aman dijalankan ulang.
 *
 * Jalankan:
 *   php artisan migrate --path=database/migrations/2026_11_26_000001_create_reconciliation_checks_table.php
 * (pengganti tanpa artisan: docs/sql/2026-11-26-cek-kecocokan.sql, atau cukup
 *  buka dashboard admin / Resume Transaksi Total: tabel dibuat otomatis)
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('reconciliation_checks')) {
            return;
        }

        Schema::create('reconciliation_checks', function (Blueprint $table) {
            $table->increments('id');
            $table->date('check_date');
            $table->unsignedInteger('cash_closing_id')->nullable();
            $table->unsignedSmallInteger('problem_count')->default(0);
            $table->mediumText('results');
            $table->dateTime('resolved_at')->nullable();
            $table->string('resolution', 20)->nullable();
            $table->string('resolved_by_role', 20)->nullable();
            $table->unsignedInteger('resolved_by_id')->nullable();
            $table->text('resolve_note')->nullable();
            $table->timestamps();

            $table->index('check_date');
            $table->index('cash_closing_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reconciliation_checks');
    }
};
