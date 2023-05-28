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
            $table->bigInteger('good_loading_id')
                  ->unsigned()
                  ->nullable();
            $table->bigInteger('good_unit_id')
                  ->unsigned()
                  ->nullable();
            $table->string('last_stock')
                  ->nullable();
            $table->decimal('quantity')
                  ->nullable();
            $table->decimal('real_quantity')
                  ->nullable();
            $table->string('price')
                  ->nullable();
            $table->string('selling_price')
                  ->nullable();
            $table->date('expiry_date')
                  ->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('good_loading_id')
                  ->references('id')
                  ->on('good_loadings')
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
        Schema::dropIfExists('good_loading_details');
    }
}
