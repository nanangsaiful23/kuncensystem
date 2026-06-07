<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Tambah index untuk optimasi laporan penjualan
 *
 * Jalankan dengan: php artisan migrate
 *
 * Index ini WAJIB ada agar query laporan tidak timeout.
 * Tanpa index, MySQL melakukan full table scan pada setiap JOIN.
 */
class AddSalesReportIndexes extends Migration
{
    public function up()
    {
        // ── transaction_details ─────────────────────────────────────────────
        // Kolom yang sering di-JOIN dan di-filter
        Schema::table('transaction_details', function (Blueprint $table) {
            // Cek dulu pakai DB::select jika ragu sudah ada
            $table->index(['transaction_id', 'deleted_at'],  'idx_td_transaction_deleted');
            $table->index('good_unit_id',                    'idx_td_good_unit');
        });

        // ── transactions ─────────────────────────────────────────────────────
        Schema::table('transactions', function (Blueprint $table) {
            // Composite: type + deleted_at + created_at → paling sering dipakai bareng
            $table->index(['type', 'deleted_at', 'created_at'], 'idx_trx_type_deleted_date');
            $table->index('member_id',                          'idx_trx_member');
        });

        // ── good_units ────────────────────────────────────────────────────────
        Schema::table('good_units', function (Blueprint $table) {
            $table->index(['good_id', 'deleted_at'], 'idx_gu_good_deleted');
        });

        // ── goods ─────────────────────────────────────────────────────────────
        Schema::table('goods', function (Blueprint $table) {
            $table->index(['category_id', 'deleted_at'], 'idx_goods_cat_deleted');
            $table->index('brand_id',                    'idx_goods_brand');
        });

        // ── good_loadings ─────────────────────────────────────────────────────
        Schema::table('good_loadings', function (Blueprint $table) {
            $table->index(['distributor_id', 'loading_date', 'deleted_at'], 'idx_gl_dist_date_deleted');
        });

        // ── journals ──────────────────────────────────────────────────────────
        Schema::table('journals', function (Blueprint $table) {
            $table->index(['journal_date', 'deleted_at'], 'idx_journal_date_deleted');
        });

        // ── piutang_payments ──────────────────────────────────────────────────
        Schema::table('piutang_payments', function (Blueprint $table) {
            $table->index(['member_id', 'deleted_at'], 'idx_pp_member_deleted');
        });
    }

    public function down()
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            $table->dropIndex('idx_td_transaction_deleted');
            $table->dropIndex('idx_td_good_unit');
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_trx_type_deleted_date');
            $table->dropIndex('idx_trx_member');
        });
        Schema::table('good_units', function (Blueprint $table) {
            $table->dropIndex('idx_gu_good_deleted');
        });
        Schema::table('goods', function (Blueprint $table) {
            $table->dropIndex('idx_goods_cat_deleted');
            $table->dropIndex('idx_goods_brand');
        });
        Schema::table('good_loadings', function (Blueprint $table) {
            $table->dropIndex('idx_gl_dist_date_deleted');
        });
        Schema::table('journals', function (Blueprint $table) {
            $table->dropIndex('idx_journal_date_deleted');
        });
        Schema::table('piutang_payments', function (Blueprint $table) {
            $table->dropIndex('idx_pp_member_deleted');
        });
    }
}