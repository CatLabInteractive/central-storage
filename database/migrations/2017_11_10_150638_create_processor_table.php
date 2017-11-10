<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProcessorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('processors', function(Blueprint $table) {

            $table->increments('id');

            $table->integer('consumer_id')->unsigned();
            $table->foreign('consumer_id')->references('id')->on('consumers');

            $table->string('variation_name', 32);
            $table->unique([ 'consumer_id', 'variation_name' ]);

            $table->boolean('default_variation');

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
        Schema::dropIfExists('processors');
    }
}
