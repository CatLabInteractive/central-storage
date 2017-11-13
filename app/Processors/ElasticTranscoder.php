<?php

namespace App\Processors;

use App\Models\Processor;

/**
 * Class ElasticTranscoder
 * @package App\Processors
 */
class ElasticTranscoder extends Processor
{
    /**
     * @return array
     */
    public function getConfigValidation()
    {
        return [
            'pipeline' => 'required',
            'preset' => 'required',
            'key' => 'required',
            'secret' => 'required',
            'region' => 'required',
        ];
    }
}