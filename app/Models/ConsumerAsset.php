<?php

namespace App\Models;

use CatLab\Assets\Laravel\Models\Asset;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ConsumerAsset
 * @package App\Models
 */
class ConsumerAsset extends Model
{
    const KEY_LENGTH = 24;

    /**
     * @param Asset $asset
     * @param Consumer $consumer
     * @return ConsumerAsset
     */
    public static function createFromAsset(Asset $asset, Consumer $consumer)
    {
        $consumerAsset = new self();
        $consumerAsset->key = self::createUniqueKey();
        $consumerAsset->consumer()->associate($consumer);
        $consumerAsset->asset()->associate($asset);

        return $consumerAsset;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function consumer()
    {
        return $this->belongsTo(Consumer::class);
    }

    /**
     * @return string
     */
    public static function createUniqueKey()
    {
        do {
            $key = str_random(self::KEY_LENGTH);
        } while (
            self::whereKey($key)->count() > 0
        );
        return $key;
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeKey($query)
    {
        return $query->where('key', '=', $query);
    }

    /**
     * Return the data that should be exposed to the consumers
     * @return array
     */
    public function getData()
    {
        return array_merge([
            'key' => $this->key,
            'name' => $this->name
        ], $this->asset->getMetaData());
    }
}