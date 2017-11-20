<?php

namespace App\Models;

use CatLab\Assets\Laravel\Models\Variation;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProcessorJob
 * @package App\Models
 */
class ProcessorJob extends Model
{
    const STATE_PREPARED = 'PREPARED';
    const STATE_PENDING = 'PENDING';
    const STATE_FINISHED = 'FINISHED';
    const STATE_FAILED = 'FAILED';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function consumerAsset()
    {
        return $this->belongsTo(ConsumerAsset::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function processor()
    {
        return $this->belongsTo(Processor::class);
    }

    /**
     * @param $state
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @param $identifier
     * @return $this
     */
    public function setExternalId($identifier)
    {
        $this->external_id = $identifier;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFinished()
    {
        switch ($this->state) {
            case ProcessorJob::STATE_FINISHED:
            case ProcessorJob::STATE_FAILED:
                return true;

            case ProcessorJob::STATE_PENDING:
            case ProcessorJob::STATE_PREPARED:
                return false;

            default:
                throw new \InvalidArgumentException('Invalid job state: ' . $this->state);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variations()
    {
        return $this->hasMany(Variation::class);
    }

    public function locks()
    {
        return $this->hasMany(ProcessorLock::class);
    }

    /**
     * @return bool
     */
    public function lockJob()
    {
        $result = false;
        \DB::transaction(function() use (&$result) {

            if ($this->locks()->count() > 0) {
                $result = false;
                return;
            }

            $lock = new ProcessorLock();
            $this->locks()->save($lock);

            $result = true;
        });

        return $result;
    }

    /**
     * Unlock the job
     */
    public function unlockJob()
    {
        $this->locks()->delete();
    }
}