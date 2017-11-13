<?php

namespace App\Models;

use App\Processors\ElasticTranscoder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Processor
 *
 * A processor prepares assets.
 *
 * @package App\Models
 */
class Processor extends Model
{
    protected $fillable = [
        'processor',
        'variation_name',
        'default_variation'
    ];

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
     * @param array $attributes
     * @param null $connection
     * @return Processor
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        foreach (self::getProcessors() as $v) {
            if ($attributes->processor === class_basename($v)) {
                $model = new $v();
            }
        }

        if (!isset($model)) {
            $model = new self();
        }

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
     * @return array
     */
    public function getConfigValidation()
    {
        return [];
    }

    /**
     * @param $key
     * @return string
     */
    public function getConfig($key)
    {
        $config = $this->config->where('name', '=', $key)->first();
        if ($config) {
            return $config->value;
        }
        return null;
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
}