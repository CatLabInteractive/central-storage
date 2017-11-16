<?php

namespace App\Models;

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
}