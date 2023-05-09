<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoodLoadingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('good_loadings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('role')
                  ->nullable();
            $table->bigInteger('role_id')
                  ->nullable();
            $table->string('checker')
                  ->nullable();
            $table->date('loading_date')
                  ->nullable();
            $table->bigInteger('distributor_id')
                  ->unsigned()
                  ->nullable();
            $table->string('total_item_price')
                  ->nullable();
            $table->string('note')
                  ->nullable();
            $table->string('payment')
                  ->nullable('cash/credit');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('distributor_id')
                  ->references('id')
                  ->on('distributors')
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
        Schema::dropIfExists('good_loadings');
    }
}
