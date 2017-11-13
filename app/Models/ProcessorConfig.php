<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ProcessorConfig
 * @package App\Models
 */
class ProcessorConfig extends Model
{
    protected $table = 'processor_config';

    protected $fillable = [
        'name',
        'value'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function processor()
    {
        return $this->belongsTo(Processor::class);
    }
}