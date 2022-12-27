<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoodPhotosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('good_photos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('good_id')
                  ->unsigned()
                  ->nullable();
            $table->string('server')
                  ->nullable();
            $table->string('location')
                  ->nullable();
            $table->boolean('is_profile_picture') 
                  ->default(0)
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
        Schema::dropIfExists('good_photos');
    }
}
