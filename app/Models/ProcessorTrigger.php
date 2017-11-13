<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 *
 */
class ProcessorTrigger extends Model
{
    protected $fillable = [
        'mimetype'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function processor()
    {
        return $this->belongsTo(Processor::class);
    }
}