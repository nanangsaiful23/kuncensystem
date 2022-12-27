<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code')
                  ->unique()
                  ->nullable();
            $table->string('name')
                  ->nullable();
            $table->string('eng_name')
                  ->nullable();
            $table->bigInteger('unit_id')
                  ->unsigned()
                  ->nullable();
                  
            $table->timestamps();
            $table->softDeletes();

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
        Schema::dropIfExists('categories');
    }
}
