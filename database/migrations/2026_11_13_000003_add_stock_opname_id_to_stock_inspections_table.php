<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menandai sidak yang selisihnya sudah dijadikan Stock Opname, supaya satu
 * sidak tidak bisa mengoreksi stok dua kali.
 *
 * Jalankan:
 *   php artisan migrate --path=database/migrations/2026_11_13_000003_add_stock_opname_id_to_stock_inspections_table.php
 * (pengganti tanpa artisan: docs/sql/2026-11-13-sidak-jadi-stock-opname.sql)
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('stock_inspections', 'stock_opname_id')) {
            Schema::table('stock_inspections', function (Blueprint $table) {
                $table->unsignedBigInteger('stock_opname_id')->nullable()->after('finished_at');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('stock_inspections', 'stock_opname_id')) {
            Schema::table('stock_inspections', function (Blueprint $table) {
                $table->dropColumn('stock_opname_id');
            });
        }
    }
};
