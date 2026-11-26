<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak siapa yang membuat stok minus dan siapa yang mengubah data stok.
 *
 * stock_minus_logs: satu baris setiap kali stok barang turun di bawah nol
 * (App\Services\StockService::sync()). Berisi aksi penyebabnya (penjualan,
 * edit transaksi, hapus loading, ...), transaksi/loading-nya dan akun yang
 * melakukannya. ended_at diisi saat stok kembali >= 0; selama masih kosong,
 * nama akun itu tampil di daftar barang minus dan di cek kecocokan harian.
 * Barang yang sudah minus saat tabel dibuat dicatat sebagai
 * 'sebelum_pencatatan' (penyebabnya tidak diketahui).
 *
 * action_logs: siapa yang mengedit/menghapus loading dan mengedit/membatalkan
 * transaksi, dengan ringkasan perubahannya.
 *
 * Aman dijalankan ulang.
 *
 * Jalankan:
 *   php artisan migrate --path=database/migrations/2026_11_26_000002_create_stock_minus_logs_table.php
 * (pengganti tanpa artisan: docs/sql/2026-11-26-catat-stok-minus.sql, atau cukup
 *  buka dashboard admin: tabel dibuat otomatis)
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('stock_minus_logs')) {
            Schema::create('stock_minus_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedBigInteger('good_id');
                $table->decimal('stock', 15, 2)->default(0);
                $table->string('action', 30);
                $table->unsignedBigInteger('transaction_id')->nullable();
                $table->unsignedBigInteger('good_loading_id')->nullable();
                $table->string('role', 20)->nullable();
                $table->unsignedBigInteger('role_id')->nullable();
                $table->string('actor_name', 100)->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('ended_at')->nullable();
                $table->timestamps();

                $table->index(['good_id', 'ended_at']);
            });

            DB::statement("INSERT INTO stock_minus_logs (good_id, stock, action, created_at, updated_at)
                SELECT g.id, g.last_stock, 'sebelum_pencatatan', NOW(), NOW() FROM goods g
                WHERE g.deleted_at IS NULL AND g.last_stock < 0");
        }

        if (! Schema::hasTable('action_logs')) {
            Schema::create('action_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->string('action', 30);
                $table->unsignedBigInteger('transaction_id')->nullable();
                $table->unsignedBigInteger('good_loading_id')->nullable();
                $table->string('role', 20)->nullable();
                $table->unsignedBigInteger('role_id')->nullable();
                $table->string('actor_name', 100)->nullable();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index('transaction_id');
                $table->index('good_loading_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('stock_minus_logs');
        Schema::dropIfExists('action_logs');
    }
};
