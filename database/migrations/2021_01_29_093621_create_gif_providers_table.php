<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGifProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gif_providers', function (Blueprint $table) {
            $table->increments('id')->key();
            $table->string('IDprovider');
            $table->string('info'); //varchar(255)
            $table->integer('counter_calls');
            $table->json('credits');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gif_providers');
    }
}
