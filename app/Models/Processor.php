<?php

namespace App\Models;

use App\Processors\ElasticTranscoder;
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

        foreach ($this->triggers as $trigger) {
            /** @var ProcessorTrigger $trigger */
            if ($trigger->check($asset)) {
                return true;
            }
        }

        return false;
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
}