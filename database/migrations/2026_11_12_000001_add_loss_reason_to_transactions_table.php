<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penyebab barang keluar pada transaksi internal "5215 - Biaya Penyusutan
 * Barang": hilang / expired / rusak. Akun & jurnalnya tetap sama (5215),
 * kolom ini hanya memisahkan penyebabnya supaya stok keluar bisa dirinci
 * (lihat App\Services\StockOutflow).
 *
 * Jalankan: php artisan migrate
 * Data lama dibiarkan NULL dan dibaca sebagai "hilang".
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('transactions', 'loss_reason')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('loss_reason', 20)->nullable()->after('type');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('transactions', 'loss_reason')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('loss_reason');
            });
        }
    }
};
