<?php

namespace App\Models;

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
     * @param $name
     * @param bool $shareGlobally TRUE to mark that this asset will always have this variation, whoever owns it.
     * @return Variation|null
     */
    public function getVariation($name, $shareGlobally = false)
    {
        $variation = $this->variations
            ->where('variation_name', '=', $name);

        if (!$shareGlobally) {
            $variation->where('consumer_id', '=', $this->getConsumerAsset()->consumer_id);
        }

        return $variation->first();
    }

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

        return $relation;
    }

    /**
     * @param array $attributes
     * @param bool $shareGlobally
     * @return Variation
     */
    protected function createVariationModel(array $attributes, $shareGlobally = false)
    {
        $variation = new Variation($attributes);
        $variation->original()->associate($this);

        if ($this->consumerAsset && !$shareGlobally) {
            $variation->consumer_id = $this->getConsumerAsset()->consumer_id;
        }

        return $variation;
    }

    /**
     * @param $variationName
     * @param \CatLab\Assets\Laravel\Models\Asset $variationAsset
     * @param bool $shareGlobally
     * @param ProcessorJob|null $job
     * @return \CatLab\Assets\Laravel\Models\Variation
     */
    public function linkVariationFromJob(
        $variationName,
        \CatLab\Assets\Laravel\Models\Asset $variationAsset,
        $shareGlobally = false,
        ProcessorJob $job = null
    ) {
        $variation = parent::linkVariation($variationName, $variationAsset, $shareGlobally);

        // Is job set?
        if (isset($job)) {
            $variation->processorJob()->associate($job);
            $variation->save();
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