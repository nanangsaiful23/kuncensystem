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
            $table->bigInteger('transaction_id')
                  ->unsigned()
                  ->nullable();
            $table->bigInteger('good_id')
                  ->unsigned()
                  ->nullable();
            $table->string('quantity')
                  ->nullable();
            $table->string('buy_price')
                  ->nullable();
            $table->string('selling_price')
                  ->nullable();
            $table->string('discount_price')
                  ->nullable();
            $table->string('sum_price')
                  ->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('transaction_id')
                  ->references('id')
                  ->on('transactions')
                  ->onDelete('cascade');

            $table->foreign('good_id')
                  ->references('id')
                  ->on('goods')
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
