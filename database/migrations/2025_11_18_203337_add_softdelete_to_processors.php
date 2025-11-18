<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftdeleteToProcessors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('processors', function (Blueprint $table) {

            $table->softDeletes();

            $table->dropForeign('processors_consumer_id_foreign');
            $table->dropUnique('processors_consumer_id_variation_name_unique');
            $table->unique(['consumer_id', 'variation_name', 'deleted_at']);
            $table->foreign('consumer_id')->references('id')->on('consumers');

        });

        Schema::table('processor_triggers', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('processors', function (Blueprint $table) {

            $table->dropForeign('processors_consumer_id_foreign');
            $table->dropUnique('processors_consumer_id_variation_name_deleted_at_unique');
            $table->dropSoftDeletes();

            $table->unique(['consumer_id', 'variation_name']);
            $table->foreign('consumer_id')->references('id')->on('consumers');

        });

        Schema::table('processor_triggers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
