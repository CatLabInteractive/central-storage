<?php

namespace App\Models;

use CatLab\Assets\Laravel\Helpers\AssetFactory;
use CatLab\Assets\Laravel\Models\Asset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Class ConsumerAsset
 * @package App\Models
 */
class ConsumerAsset extends Model
{
    use SoftDeletes;

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'expires_at'
    ];

    const KEY_LENGTH = 24;

    /**
     * @param Asset $asset
     * @param Consumer $consumer
     * @return ConsumerAsset
     */
    public static function createFromAsset(Asset $asset, Consumer $consumer)
    {
        $consumerAsset = new self();
        $consumerAsset->ca_key = self::createUniqueKey();
        $consumerAsset->consumer()->associate($consumer);
        $consumerAsset->asset()->associate($asset);

        return $consumerAsset;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function asset()
    {
        return $this->belongsTo(AssetFactory::getAssetClassName());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function consumer()
    {
        return $this->belongsTo(Consumer::class);
    }

    /**
     * @return \App\Models\Asset
     */
    public function getAsset()
    {
        $asset = $this->asset;
        $asset->setConsumerAsset($this);

        return $asset;
    }

    /**
     * @return string
     */
    public static function createUniqueKey()
    {
        do {
            $key = Str::random(self::KEY_LENGTH);
        } while (
            self::assetKey($key)->count() > 0
        );
        return $key;
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeAssetKey($query, $key)
    {
        return $query->where('ca_key', '=', $key);
    }

    /**
     * Check if this variation is still being processed.
     * @param $variationName
     * @return bool
     */
    public function isVariationProcessing($variationName)
    {
        return $this->asset->isVariationProcessing($variationName, $this->consumer);
    }

    /**
     * Return the data that should be exposed to the consumers
     * @return array
     */
    public function getData()
    {
        return array_merge([
            'key' => $this->ca_key,
            'name' => $this->name
        ], $this->asset->getMetaData());
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return action('AssetController@viewConsumerAsset', [ $this->ca_key ]);
    }
}
