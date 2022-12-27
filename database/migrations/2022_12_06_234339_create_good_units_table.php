<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoodUnitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('good_units', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('good_id')
                  ->unsigned()
                  ->nullable();
            $table->bigInteger('unit_id')
                  ->unsigned()
                  ->nullable();
            $table->string('buy_price')
                  ->nullable();
            $table->string('selling_price')
                  ->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('good_id')
                  ->references('id')
                  ->on('goods')
                  ->onDelete('cascade');

            $table->foreign('unit_id')
                  ->references('id')
                  ->on('units')
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
        Schema::dropIfExists('good_units');
    }
}
