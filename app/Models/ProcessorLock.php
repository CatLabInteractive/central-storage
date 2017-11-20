<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ProcessorLock
 * @package App\Models
 */
class ProcessorLock extends Model
{
    protected $table = 'processor_job_lock';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function processorJob()
    {
        return $this->belongsTo(ProcessorJob::class);
    }
}