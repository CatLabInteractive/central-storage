<?php

use CatLab\Assets\Laravel\Models\Asset;
use CatLab\Assets\Laravel\Models\Variation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVariationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('variations', function(Blueprint $table) {

            $table->increments('id');

            $table->integer('original_asset_id')->unsigned();
            $table->foreign('original_asset_id')->references('id')->on('assets');

            $table->integer('variation_asset_id')->unsigned();
            $table->foreign('variation_asset_id')->references('id')->on('assets');

            $table->string('variation_name', 32);
            $table->unique([ 'original_asset_id', 'variation_name' ]);

            $table->timestamps();

        });

        $assetIds = \DB::table('assets')
            ->select(\DB::raw('MIN(id) as id'))
            ->whereNotNull('root_asset_id')
            ->groupBy('root_asset_id', 'width', 'height')
            ->get()
            ->pluck('id');

        $assetIds->each(
            function($assetId) {

                $asset = Asset::find($assetId);

                $variationName = 'resized:' . $asset->width . ':' . $asset->height;

                $variation = new Variation([
                    'variation_name' => $variationName
                ]);
                $variation->original()->associate(Asset::find($asset->root_asset_id));
                $variation->asset()->associate($asset);
                $variation->save();

            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('variations');
    }
}
