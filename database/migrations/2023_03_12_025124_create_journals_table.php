<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJournalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type')
                  ->comment('jenisnya transaksi/loading/lain2')
                  ->nullable();
            $table->date('journal_date')
                  ->nullable();
            $table->string('name')
                  ->nullable();
            $table->bigInteger('debit_account_id')
                  ->unsigned()
                  ->nullable();
            $table->string('debit')
                  ->nullable();
            $table->bigInteger('credit_account_id')
                  ->unsigned()
                  ->nullable();
            $table->string('credit')
                  ->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('debit_account_id')
                  ->references('id')
                  ->on('accounts')
                  ->onDelete('cascade');

            $table->foreign('credit_account_id')
                  ->references('id')
                  ->on('accounts')
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
        Schema::dropIfExists('journals');
    }
}
