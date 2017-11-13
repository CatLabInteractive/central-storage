<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddConsumerToVariations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('variations', function(Blueprint $table) {

            $table->dropForeign('variations_original_asset_id_foreign');
            $table->dropUnique('variations_original_asset_id_variation_name_unique');

            $table->integer('consumer_id')->unsigned()->nullable()->after('id');
            $table->foreign('consumer_id')->references('id')->on('consumers');

            $table->foreign('original_asset_id')->references('id')->on('assets');

            $table->unique([ 'variation_name', 'consumer_id', 'original_asset_id' ]);

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

            //$table->dropForeign('variations_consumer_id_foreign');
            $table->dropColumn('consumer_id');
            $table->unique([ 'original_asset_id', 'variation_name' ]);

        });
    }
}
