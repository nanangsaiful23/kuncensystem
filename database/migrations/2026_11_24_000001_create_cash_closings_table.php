<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tutup Kasir harian, lihat docs/rencana-optimasi-dan-tutup-kasir.md poin 4.
 *
 * cash_closings: satu baris per tanggal (yang belum dibatalkan). Menyimpan
 * saldo Kas di Tangan menurut sistem, uang fisik hasil hitungan, selisihnya,
 * jumlah yang diserahkan ke owner, dan jurnal yang dibuat. drawers (JSON):
 * rincian uang fisik per laci, karena dua kasir bisa memakai laci sendiri
 * walaupun akun Kas di Tangan-nya satu.
 *
 * journals + created_by_role/created_by_id: akun (admin/kasir) yang menginput
 * jurnal, diisi otomatis oleh model Journal. Dipakai untuk panduan per laci
 * (transaksi lain & biaya lain yang diinput kasir itu). Jurnal lama kosong.
 * Namanya sengaja bukan "role": banyak query menggabung journals dengan
 * tabel lain yang punya kolom role.
 *
 * Semua langkah dicek dulu, jadi aman dijalankan ulang.
 *
 * Jalankan:
 *   php artisan migrate --path=database/migrations/2026_11_24_000001_create_cash_closings_table.php
 * (pengganti tanpa artisan: docs/sql/2026-11-24-tutup-kasir.sql, atau cukup
 *  buka halaman Resume Transaksi Total: tabel & kolom dibuat otomatis)
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('cash_closings')) {
            Schema::create('cash_closings', function (Blueprint $table) {
                $table->increments('id');
                $table->date('closing_date');
                $table->decimal('system_balance', 14, 2);
                $table->decimal('physical_cash', 14, 2);
                $table->text('drawers')->nullable();
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
        } elseif (! Schema::hasColumn('cash_closings', 'drawers')) {
            // Tabel dari versi awal (sebelum ada rincian per laci).
            Schema::table('cash_closings', function (Blueprint $table) {
                $table->text('drawers')->nullable()->after('physical_cash');
            });
        }

        if (! Schema::hasColumn('journals', 'created_by_role')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->string('created_by_role', 20)->nullable();
                $table->unsignedInteger('created_by_id')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('journals', 'created_by_role')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->dropColumn(['created_by_role', 'created_by_id']);
            });
        }
        Schema::dropIfExists('cash_closings');
    }
};
