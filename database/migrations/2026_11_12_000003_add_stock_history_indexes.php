<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * StockService::history() sums real_quantity per good. These covering indexes
 * let MySQL answer it from the index alone (about 10 s -> 5 ms for a good with
 * 6000 sales lines), and the deleted_at indexes make the "deleted parents"
 * lookups instant. Saving a sale with many goods recalculates every good.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            $table->index(['good_unit_id', 'deleted_at', 'type', 'real_quantity', 'transaction_id'], 'idx_td_stock');
        });
        Schema::table('good_loading_details', function (Blueprint $table) {
            $table->index(['good_unit_id', 'deleted_at', 'real_quantity', 'good_loading_id'], 'idx_gld_stock');
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('deleted_at', 'idx_trx_deleted_at');
        });
        Schema::table('good_loadings', function (Blueprint $table) {
            $table->index('deleted_at', 'idx_gl_deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_details', fn (Blueprint $t) => $t->dropIndex('idx_td_stock'));
        Schema::table('good_loading_details', fn (Blueprint $t) => $t->dropIndex('idx_gld_stock'));
        Schema::table('transactions', fn (Blueprint $t) => $t->dropIndex('idx_trx_deleted_at'));
        Schema::table('good_loadings', fn (Blueprint $t) => $t->dropIndex('idx_gl_deleted_at'));
    }
};
