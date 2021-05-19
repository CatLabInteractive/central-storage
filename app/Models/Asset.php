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
            $variation = $variation->where('consumer_id', '=', $this->getConsumerAsset()->consumer_id);
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
     * @param Processor $processor
     * @param $variationName
     * @param ConsumerAsset $consumerAsset
     * @param \CatLab\Assets\Laravel\Models\Asset $variationAsset
     * @param bool $shareGlobally
     * @param ProcessorJob|null $job
     * @return \CatLab\Assets\Laravel\Models\Variation
     */
    public function linkVariationFromJob(
        Processor $processor,
        $variationName,
        ConsumerAsset $consumerAsset,
        \CatLab\Assets\Laravel\Models\Asset $variationAsset,
        $shareGlobally = false,
        ProcessorJob $job = null
    ) {
        /** @var Variation $variation */
        $variation = parent::linkVariation($variationName, $variationAsset, $shareGlobally);

        $variation->processorJob()->associate($job);
        $variation->processor()->associate($processor);
        $variation->save();

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

    /**
     * @param $variationName
     * @param Consumer $consumer
     * @return bool
     */
    public function isVariationProcessing($variationName, Consumer $consumer)
    {
        // Look for the job that generates this variation.
        foreach ($consumer->processors as $processor) {
            /** @var Processor $processor */
            if ($processor->doesGenerateVariation($variationName)) {
                // look for a job for this asset.
                $jobs = $processor
                    ->jobs()
                    ->where('consumer_asset_id', '=', $this->id)
                    ->where('state', ProcessorJob::STATE_PENDING)
                    ->count();

                if ($jobs > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $destination
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function saveToFile($destination)
    {
        $writeStream = fopen($destination, 'w+');

        $disk = $this->getDisk();
        $stream = $disk->readStream($this->path);
        while (!feof($stream)) {
            fwrite($writeStream, fgets($stream, 1024));
        }
        fclose($writeStream);
        fclose($stream);
    }
}
