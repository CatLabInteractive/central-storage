<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOriginalJobToJobs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('processor_jobs', function(Blueprint $table) {

            $table->integer('original_job_id')->unsigned()->nullable()->after('processor_id');
            $table->foreign('original_job_id')->references('id')->on('processor_jobs');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('processor_jobs', function(Blueprint $table) {

            $table->dropForeign('processor_jobs_original_job_id_foreign');
            $table->dropColumn('original_job_id');

        });
    }
}
