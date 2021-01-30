<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRelationprovkeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('relationsprovkey', function (Blueprint $table) {
            $table->increments('idrelation')->key;
            $table->integer('IDproviderR')->unsigned();
            $table->foreign('IDproviderR')->references('id')->on('gif_providers');
            $table->integer('idkeywordR')->unsigned();
            $table->foreign('idkeywordR')->references('id')->on('keywords');
            $table->integer('counter');

            
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('relationprovkeys');
    }
}
