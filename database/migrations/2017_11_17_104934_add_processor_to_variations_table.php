<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProcessorToVariationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('variations', function(Blueprint $table) {
            $table->integer('processor_job_id')->nullable()->unsigned()->after('variation_asset_id');
            $table->foreign('processor_job_id')->references('id')->on('processor_jobs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('variations', function(Blueprint $table) {
            $table->dropForeign('variations_processor_job_id_foreign');
            $table->dropColumn('processor_job_id');
        });
    }
}
