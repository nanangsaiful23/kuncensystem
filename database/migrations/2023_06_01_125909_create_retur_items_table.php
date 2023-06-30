<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('retur_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('good_id')
                  ->unsigned()
                  ->nullable();
            $table->bigInteger('last_distributor_id')
                  ->unsigned()
                  ->nullable();
            $table->date('returned_date')
                  ->nullable();
            $table->string('returned_type')
                  ->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('good_id')
                  ->references('id')
                  ->on('goods')
                  ->onDelete('cascade');

            $table->foreign('last_distributor_id')
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
        Schema::dropIfExists('retur_items');
    }
}
