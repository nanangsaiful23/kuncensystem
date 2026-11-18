<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hak akses menu per role (halaman Pengaturan > Hak Akses Menu).
 * Daftar menu dan nilai bawaannya ada di config/menu_akses.php.
 *
 * Jalankan:
 *   php artisan migrate --path=database/migrations/2026_11_18_000002_create_menu_permissions_table.php
 * (pengganti tanpa artisan: docs/sql/2026-11-18-tambah-hak-akses-menu.sql)
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('menu_permissions')) {
            Schema::create('menu_permissions', function (Blueprint $table) {
                $table->string('role', 20);
                $table->string('menu_key', 60);
                $table->boolean('allowed')->default(false);
                $table->timestamp('updated_at')->nullable();

                $table->primary(['role', 'menu_key']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('menu_permissions');
    }
};
