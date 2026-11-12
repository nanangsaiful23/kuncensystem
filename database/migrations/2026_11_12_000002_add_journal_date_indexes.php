<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saving a sale looks up today's journals by (type, journal_date) with
 * lockForUpdate(). Without an index that is a full scan of the journals table
 * (~0.8 s) and InnoDB locks every row it scans, so a second cashier (or a retry)
 * waited for the lock until PHP stopped at 30 seconds.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->index(['type', 'journal_date'], 'idx_journals_type_date');
            $table->index('journal_date', 'idx_journals_date');
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropIndex('idx_journals_type_date');
            $table->dropIndex('idx_journals_date');
        });
    }
};
