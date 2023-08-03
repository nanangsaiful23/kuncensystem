<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type')
                  ->nullable();
            $table->bigInteger('transaction_id')
                  ->unsigned()
                  ->nullable();
            $table->bigInteger('good_unit_id')
                  ->unsigned()
                  ->nullable();
            $table->decimal('quantity')
                  ->nullable();
            $table->decimal('real_quantity')
                  ->nullable();
            $table->decimal('last_stock')
                  ->nullable();
            $table->decimal('buy_price')
                  ->nullable();
            $table->decimal('selling_price')
                  ->nullable();
            $table->decimal('discount_price')
                  ->nullable();
            $table->decimal('sum_price')
                  ->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('transaction_id')
                  ->references('id')
                  ->on('transactions')
                  ->onDelete('cascade');

            $table->foreign('good_unit_id')
                  ->references('id')
                  ->on('good_units')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_details');
    }
}
