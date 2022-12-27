<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoodLoadingDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('good_loading_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('good_id')
                  ->unsigned()
                  ->nullable();
            $table->string('last_stock')
                  ->nullable();
            $table->string('quantity')
                  ->nullable();
            $table->string('price')
                  ->nullable();
            $table->date('expiry_date')
                  ->nullable();

            $table->timestamps();
            $table->softDeletes();

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
        Schema::dropIfExists('good_loading_details');
    }
}
