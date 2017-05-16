<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TransformKeyToBinary extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('consumer_assets', function(Blueprint $table) {

            $table->renameColumn('key', 'ca_key');
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
        Schema::table('consumer_assets', function(Blueprint $table) {

            $table->renameColumn('ca_key', 'key');
            $table->dropSoftDeletes();

        });
    }
}
