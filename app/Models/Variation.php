<?php

namespace App\Models;

/**
 * Class Variation
 * @package App\Models
 */
class Variation extends \CatLab\Assets\Laravel\Models\Variation
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function processorJob()
    {
        return $this->belongsTo(ProcessorJob::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function processor()
    {
        return $this->belongsTo(Processor::class);
    }
}