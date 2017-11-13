<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProcessorConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('processor_config', function(Blueprint $table) {

            $table->increments('id');

            $table->integer('processor_id')->unsigned();
            $table->foreign('processor_id')->references('id')->on('processors');

            $table->string('name');
            $table->unique([ 'processor_id', 'name' ]);

            $table->string('value')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('processor_config');
    }
}
