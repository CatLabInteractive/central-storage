<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobLockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('processor_job_lock', function(Blueprint $table) {

            $table->increments('id');

            $table->integer('processor_job_id')->unsigned();
            $table->foreign('processor_job_id')->references('id')->on('processor_jobs');
            $table->unique('processor_job_id');

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
        Schema::dropIfExists('processor_job_lock');
    }
}
