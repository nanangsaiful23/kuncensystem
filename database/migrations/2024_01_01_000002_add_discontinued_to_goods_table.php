<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Tambah kolom discontinued ke tabel goods
 * Jalankan: php artisan migrate
 *
 * is_discontinued    → flag 0/1
 * discontinued_at    → timestamp kapan di-discontinue
 * discontinued_reason→ alasan (teks bebas dari user)
 * discontinued_by    → user_id yang melakukan aksi
 */
class AddDiscontinuedToGoodsTable extends Migration
{
    public function up()
    {
        Schema::table('goods', function (Blueprint $table) {
            $table->tinyInteger('is_discontinued')->default(0)->after('last_transaction');
            $table->timestamp('discontinued_at')->nullable()->after('is_discontinued');
            $table->text('discontinued_reason')->nullable()->after('discontinued_at');
            $table->unsignedBigInteger('discontinued_by')->nullable()->after('discontinued_reason');

            $table->index('is_discontinued', 'idx_goods_discontinued');
        });
    }

    public function down()
    {
        Schema::table('goods', function (Blueprint $table) {
            $table->dropIndex('idx_goods_discontinued');
            $table->dropColumn([
                'is_discontinued',
                'discontinued_at',
                'discontinued_reason',
                'discontinued_by',
            ]);
        });
    }
}