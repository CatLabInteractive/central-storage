<?php

namespace App\Models;

use CatLab\Assets\Laravel\Models\Variation;

/**
 * Class Asset
 * @package App\Models
 */
class Asset extends \CatLab\Assets\Laravel\Models\Asset
{
    /**
     * @var ConsumerAsset
     */
    private $consumerAsset;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variations()
    {
        /** @var \Illuminate\Database\Eloquent\Relations\HasMany $relation */
        $relation = $this
            ->hasMany(Variation::class, 'original_asset_id', 'id')
            ->with('asset')
        ;

        if ($this->consumerAsset) {
            $relation->where('consumer_id', '=', $this->getConsumerAsset()->consumer_id);
        }

        return $relation;
    }

    /**
     * @param array $attributes
     * @return Variation
     */
    protected function createVariationModel(array $attributes)
    {
        $variation = new Variation($attributes);
        $variation->original()->associate($this);

        if ($this->consumerAsset) {
            $variation->consumer_id = $this->getConsumerAsset()->consumer_id;
        }

        return $variation;
    }

    /**
     * @return ConsumerAsset
     */
    public function getConsumerAsset(): ConsumerAsset
    {
        return $this->consumerAsset;
    }

    /**
     * @param ConsumerAsset $consumerAsset
     * @return Asset
     */
    public function setConsumerAsset(ConsumerAsset $consumerAsset): Asset
    {
        $this->consumerAsset = $consumerAsset;
        return $this;
    }
}