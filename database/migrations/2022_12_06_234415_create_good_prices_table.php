<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoodPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('good_prices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('role')
                  ->nullable();
            $table->bigInteger('role_id')
                  ->nullable();
            $table->bigInteger('good_unit_id')
                  ->unsigned()
                  ->nullable();
            $table->string('old_price')
                  ->nullable();
            $table->string('recent_price')
                  ->nullable();
            $table->string('reason')
                  ->nullable();

            $table->timestamps();
            $table->softDeletes();

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
        Schema::dropIfExists('good_prices');
    }
}
