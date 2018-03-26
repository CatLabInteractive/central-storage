<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProcessorIdToVariations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('variations', function(Blueprint $table) {

            $table->integer('processor_id')->unsigned()->nullable()->after('variation_asset_id');
            $table->foreign('processor_id')->references('id')->on('processors');

        });

        DB::statement("update variations left join processor_jobs on variations.processor_job_id = processor_jobs.id set variations.processor_id = processor_jobs.processor_id;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('variations', function(Blueprint $table) {

            $table->dropForeign('variations_processor_id_foreign');
            $table->dropColumn('processor_id');

        });
    }
}
