<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProcessorJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('processor_jobs', function(Blueprint $table) {

            $table->increments('id');

            $table->integer('asset_id')->unsigned();
            $table->foreign('asset_id')->references('id')->on('assets');

            $table->integer('processor_id')->unsigned();
            $table->foreign('processor_id')->references('id')->on('processors');

            $table->enum('state', [ 'PREPARED', 'PENDING', 'FINISHED', 'FAILED' ]);

            $table->string('external_id', 64)->nullable();
            $table->index('external_id');

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
        Schema::dropIfExists('processor_jobs');
    }
}
