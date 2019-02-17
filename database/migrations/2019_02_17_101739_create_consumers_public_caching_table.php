<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConsumersPublicCachingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('consumer_cached_proxy_file', function(Blueprint $table) {

            $table->increments('id');

            $table->integer('asset_id')->unsigned();
            $table->foreign('asset_id')->references('id')->on('assets');

            $table->integer('consumer_id')->unsigned();
            $table->foreign('consumer_id')->references('id')->on('consumers');

            $table->text('public_url');

            $table->string('public_url_hash', 32);
            $table->unique([ 'public_url_hash', 'consumer_id' ]);

            $table->dateTime('expires_at');

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
        Schema::dropIfExists('consumer_cached_proxy_file');
    }
}
