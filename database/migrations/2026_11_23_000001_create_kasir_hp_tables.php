<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kasir HP (APK Android) tahap 1, lihat docs/rancangan-kasir-hp-tahap1.md.
 *
 * - pos_devices      : HP yang boleh mengirim transaksi.
 * - pos_sync_rejects : transaksi dari HP yang ditolak server (dicek admin).
 * - transactions     : + client_uuid (anti-dobel), pos_device_id, receipt_no, sold_at.
 *
 * Semua penambahan; transaksi desktop tidak terpengaruh (kolom baru null).
 *
 * Jalankan:
 *   php artisan migrate --path=database/migrations/2026_11_23_000001_create_kasir_hp_tables.php
 * (pengganti tanpa artisan: docs/sql/2026-11-23-kasir-hp-tahap1.sql, atau cukup
 *  buka halaman Pengaturan > Kasir HP sebagai superadmin: tabel dibuat otomatis)
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('pos_devices')) {
            Schema::create('pos_devices', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 100);
                $table->string('code', 10)->unique();
                $table->char('token_hash', 64)->nullable()->unique();
                $table->string('pair_code', 10)->nullable();
                $table->dateTime('pair_expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->dateTime('last_seen_at')->nullable();
                $table->dateTime('last_sync_at')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('pos_sync_rejects')) {
            Schema::create('pos_sync_rejects', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('pos_device_id');
                $table->char('client_uuid', 36)->nullable();
                $table->string('receipt_no', 30)->nullable();
                $table->longText('payload');
                $table->text('reason');
                $table->dateTime('resolved_at')->nullable();
                $table->timestamps();

                $table->index('client_uuid');
            });
        }

        if (! Schema::hasColumn('transactions', 'client_uuid')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->char('client_uuid', 36)->nullable()->unique()->after('id');
                $table->unsignedInteger('pos_device_id')->nullable()->after('client_uuid');
                $table->string('receipt_no', 30)->nullable()->after('pos_device_id');
                $table->dateTime('sold_at')->nullable()->after('receipt_no');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('transactions', 'client_uuid')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropUnique(['client_uuid']);
                $table->dropColumn(['client_uuid', 'pos_device_id', 'receipt_no', 'sold_at']);
            });
        }
        Schema::dropIfExists('pos_sync_rejects');
        Schema::dropIfExists('pos_devices');
    }
};
