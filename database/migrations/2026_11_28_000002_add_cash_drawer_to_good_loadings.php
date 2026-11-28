<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laci asal uang loading barang yang dibayar dari 1111 Kas di Tangan.
 *
 * good_loadings.cash_drawer : kunci laci Tutup Kasir ("cashier:15", "admin:1"),
 *   sama dengan kunci di App\Services\TutupKasir::daftarLaci(). Jurnal loading
 *   dan pembulatannya mengurangi perkiraan isi laci itu. NULL = belum dipilih
 *   (loading lama atau bukan bayar dari Kas di Tangan); uangnya tampil sebagai
 *   "Pengeluaran kas belum dialokasikan ke laci" di Tutup Kasir.
 *
 * Aman dijalankan ulang.
 *
 * Jalankan:
 *   php artisan migrate --path=database/migrations/2026_11_28_000002_add_cash_drawer_to_good_loadings.php
 * (pengganti tanpa artisan: docs/sql/2026-11-28-laci-loading-kas.sql, atau cukup
 *  buka halaman Tambah Loading / Resume Transaksi Total: kolom dibuat otomatis)
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('good_loadings', 'cash_drawer')) {
            Schema::table('good_loadings', function (Blueprint $table) {
                $table->string('cash_drawer', 40)->nullable()->after('payment');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('good_loadings', 'cash_drawer')) {
            Schema::table('good_loadings', fn (Blueprint $table) => $table->dropColumn('cash_drawer'));
        }
    }
};
