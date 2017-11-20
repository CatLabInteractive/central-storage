<?php

namespace App\Models;

use App\Processors\ElasticTranscoder;
use CatLab\Assets\Laravel\Models\Variation;
use CatLab\Assets\Laravel\PathGenerators\PathGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Validation\ValidationException;
use Request;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Validator;

/**
 * Class Processor
 *
 * A processor prepares assets.
 *
 * @package App\Models
 */
class Processor extends Model
{
    const PATH_PREFIX = 'proc/';

    protected $fillable = [
        'processor',
        'variation_name',
        'default_variation'
    ];

    /**
     * @var OutputInterface
     */
    protected $output;

    protected $table = 'processors';

    /**
     * @return array
     */
    public static function getProcessors()
    {
        return [
            ElasticTranscoder::class
        ];
    }

    /**
     * Get all jobs from external id
     * @param $externalId
     * @return Builder
     */
    public static function getJobsByExternalId($externalId)
    {
        return ProcessorJob::select('processor_jobs.*')
            ->where('external_id', '=', $externalId)
            ->leftJoin('processors', 'processors.id', '=', 'processor_jobs.processor_id')
            ->where('processors.processor', '=', class_basename(get_called_class()))
            ->with('processor')
        ;
    }

    /**
     * Processor constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->output = new NullOutput();
    }

    /**
     * Get a processor from a (base) classname.
     * @param $name
     * @return Processor
     */
    public static function getFromClassName($name)
    {
        foreach (self::getProcessors() as $v) {
            if (strtolower($name) === strtolower(class_basename($v))) {
                $model = new $v();
            }
        }

        if (!isset($model)) {
            $model = new self();
        }

        return $model;
    }

    /**
     * Called when an external processor tries to notify about job progress.
     * Note that this is called on an EMPTY processor (no id set yet)
     * So first job should be to find the right processor.
     * @param \Illuminate\Http\Request $request
     */
    public static function notify(\Illuminate\Http\Request $request)
    {
        // Not implemented here.
    }

    /**
     * @param ConsumerAsset $consumerAsset
     */
    public final function process(ConsumerAsset $consumerAsset)
    {
        $asset = $consumerAsset->asset;

        try {
            $this->validateConfig();
        } catch (ValidationException $e) {
            $this->output->writeln('ERROR: Processor config failed validation');
            return;
        }

        // Look for an identical processor that might have processed this asset already
        if ($this->linkExistingVariations($consumerAsset)) {
            return;
        }

        $outputPath = $this->getOutputPath($consumerAsset);

        $job = new ProcessorJob();
        $job->asset_id = $consumerAsset->getAsset()->id;
        $job->consumerAsset()->associate($consumerAsset);
        $job->processor()->associate($this);
        $job->setState(ProcessorJob::STATE_PREPARED);

        $this->output->writeln('Processing ' . $asset->id . ' -> ' . $outputPath);
        $this->handle($job, $outputPath);

        $job->save();
    }

    /**
     * This method is very important. Since duplicate assets are avoided, one asset an have multiple CustomerAssets.
     * All these CustomerAssets have their own variations (since variations are customer-specific), but that doesn't
     * mean they should create duplicate assets.
     *
     * So this method looks for assets that were generated with "similar" processors (= a processor of the same type
     * with the same configuration) and, if such processor exists, it creates customer specific variations from
     * the existing assets.
     *
     * Returns TRUE if such link was found (and thus no further processing is required) or FALSE when the processor
     * should do its job.
     *
     * @param ConsumerAsset $consumerAsset
     * @return bool
     */
    protected function linkExistingVariations(ConsumerAsset $consumerAsset)
    {
        $asset = $consumerAsset->asset;

        // Are we processing this asset now?
        $existingJobs = $this->jobs()->where('asset_id', '=', $asset->id);

        // Already processing this asset? Then don't continue.
        // The resulting variations will be ours anyway, so we will be able to just use them.
        if ($existingJobs->count() > 0) {
            return true;
        }

        // Did we ever process this asset already?
        $existingAssetVariations = Variation::select('variations.*')
            ->where('original_asset_id', '=', $asset->id)
            ->leftJoin('processor_jobs', 'processor_jobs.id', '=', 'variations.processor_job_id')
            ->where('processor_jobs.processor_id', '=', $this->id)
            ->count();

        // If we have variations on this asset by earlier processes, don't try to create new ones.
        if ($existingAssetVariations > 0) {
            return true;
        }

        // Next up, check for similar processors that have already created variations like this.
        // These are always owned by a different consumer.
        $existingVariation = Variation::select('variations.*')
            ->where('original_asset_id', '=', $asset->id)
            ->with('processorJob')
            ->get();

        $processedProcessors = [];

        $result = false;
        foreach ($existingVariation as $variation) {
            /** @var Variation $variation */
            $job = $variation->processorJob;

            if (!isset($variation->processorJob)) {
                continue;
            }

            // make sure to process every processor only once.
            $processorId = $variation->processorJob->processor_id;
            if (isset($processedProcessors[$processorId])) {
                continue;
            }

            $processedProcessors[$processorId] = true;

            if ($job->processor->consumer_id === $this->consumer_id) {
                throw new \LogicException("A variation created by the same owner is found. This is impossible.");
            }

            // Is the processor similar to this processor?
            if ($this->isSimilar($job->processor)) {
                // This is basically the same job, so all variations that this job has created are applicable to this asset as well.
                $result = true;

                $variations = $job->variations;
                foreach ($variations as $variation) {
                    /** @var Variation $varation */

                    // replace the name with the name of this processor
                    $newVariationName = str_replace($job->processor->variation_name, $this->variation_name, $variation->variation_name);

                    // link this new variation
                    $newAsset = $variation->asset;

                    $asset->linkVariation($newVariationName, $newAsset, $job);
                }
            }
        }

        return $result;
    }

    /**
     * @param ProcessorJob $job
     * @param $outputPath
     * @internal param Asset $asset
     */
    protected function handle(ProcessorJob $job, $outputPath)
    {
        $this->output->writeln('ERROR! No processing code found.');
    }

    /**
     * @param ProcessorJob $job
     */
    public final function updateJob(ProcessorJob $job)
    {
        $this->handleUpdate($job);
        $job->save();
    }

    /**
     * @param ProcessorJob $job
     */
    protected function handleUpdate(ProcessorJob $job)
    {

    }

    /**
     * @param array $attributes
     * @param null $connection
     * @return Processor
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $model = self::getFromClassName($attributes->processor);

        $model->exists = true;
        $model->setRawAttributes((array) $attributes, true);
        $model->setConnection($connection ?: $this->getConnectionName());

        return $model;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function config()
    {
        return $this->hasMany(ProcessorConfig::class, 'processor_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function triggers()
    {
        return $this->hasMany(ProcessorTrigger::class, 'processor_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function consumer()
    {
        return $this->belongsTo(Consumer::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function jobs()
    {
        return $this->hasMany(ProcessorJob::class, 'processor_id');
    }

    /**
     * @return array
     */
    public function getConfigValidation()
    {
        return [];
    }

    /**
     * @param $key
     * @param null $default
     * @return string
     */
    public function getConfig($key, $default = null)
    {
        $config = $this->config->where('name', '=', $key)->first();
        if ($config) {
            return $config->value;
        }
        return $default;
    }

    /**
     * @param $config
     */
    public function saveConfig(array $config)
    {
        foreach ($config as $k => $v) {
            $config = $this->touchConfigModel($k);
            $config->value = $v;

            $config->save();
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->processor . ': ' . $this->variation_name;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getProcessBatch()
    {
        $consumerAssets = ConsumerAsset::query();

        $consumerAssets->select('consumer_assets.*');

        // only return assets from this consumer
        $consumerAssets->where('consumer_id', '=', $this->consumer->id);

        // left join assets
        $consumerAssets->leftJoin('assets', 'consumer_assets.asset_id', '=', 'assets.id');

        // left join any jobs that have been registered already
        $consumerAssets->leftJoin('processor_jobs', 'assets.id', '=', 'processor_jobs.asset_id');
        $consumerAssets->whereNull('processor_jobs.id');

        // filter on mimetype (this is going to be super slow.)
        $consumerAssets->where(
            function($query) {
                foreach ($this->triggers as $trigger) {
                    /** @var ProcessorTrigger $trigger */
                    $trigger->addToQuery($query);
                }
            }
        );

        $consumerAssets->orderBy('consumer_assets.id', 'asc');

        return $consumerAssets;
    }

    /**
     * @return Builder
     */
    public function getPendingJobs()
    {
        return $this->jobs->where('state', '=', ProcessorJob::STATE_PENDING);
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Check if this processor generates the "default variation" that will be returned to the user.
     * @param ConsumerAsset $asset
     * @return bool
     */
    public function isDefaultVariation(ConsumerAsset $asset)
    {
        if (!$this->default_variation) {
            return false;
        }

        return $this->isTriggered($asset);
    }

    /**
     * @param ConsumerAsset $asset
     * @return bool
     */
    public function isTriggered(ConsumerAsset $asset)
    {
        foreach ($this->triggers as $trigger) {
            /** @var ProcessorTrigger $trigger */
            if ($trigger->check($asset)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the name of the desired variation (based on request parameters)
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function getDesiredVariation(\Illuminate\Http\Request $request)
    {
        return $this->variation_name;
    }

    /**
     * @param $key
     * @return ProcessorConfig
     */
    protected function touchConfigModel($key)
    {
        $config = $this->config->where('name', '=', $key)->first();
        if ($config) {
            return $config;
        }

        $config = new ProcessorConfig();
        $config->name = $key;
        $config->processor()->associate($this);

        return $config;
    }

    /**
     * @param ConsumerAsset $consumerAsset
     * @return string
     */
    protected function getOutputPath(ConsumerAsset $consumerAsset)
    {
        $path = $consumerAsset->asset->path;
        $parts = explode('/', $path);
        $name = array_pop($parts);

        $name = explode('.', $name);
        array_pop($name);
        $name = implode('.', $name);

        $path = implode('/', $parts);

        return $path . '/' . self::PATH_PREFIX . $this->id . '/' . $name;
    }

    /**
     *
     */
    protected function validateConfig()
    {
        $validator = Validator::make(
            $this->getConfigAssoc(),
            $this->getConfigValidation()
        );

        $validator->validate();
    }

    /**
     * @return string[]
     */
    protected function getConfigAssoc()
    {
        return $this->config->mapWithKeys(
            function(ProcessorConfig $v) {

                return [
                    $v->name => $v->value
                ];
            }
        )->toArray();
    }

    /**
     * @param ConsumerAsset $consumerAsset
     * @param $path
     * @return Asset
     */
    protected function createAsset(ConsumerAsset $consumerAsset, $path)
    {
        $original = $consumerAsset->asset;

        $newAsset = new Asset();
        $newAsset->path = $path;

        $newAsset->type = $original->type;
        $newAsset->mimetype = $this->getConfig('mimetype', $original->mimetype);
        $newAsset->disk = $original->disk;

        return $newAsset;
    }

    /**
     * @param Processor $processor
     * @return bool
     */
    protected function isConfigSimilar(Processor $processor)
    {
        throw new \LogicException("This method should never be called.");
    }

    /**
     * @param Processor $processor
     * @return bool|null
     */
    private function isSimilar(Processor $processor)
    {
        if ($this->id === $processor->id) {
            return true;
        }

        if ($this->processor !== $processor->processor) {
            return false;
        }

        return $this->isConfigSimilar($processor);
    }
}