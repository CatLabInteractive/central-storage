<?php

namespace App\Http\Requests;

use App\Models\Processor;

/**
 * Class EditProcessorRequest
 * @package App\Http\Requests
 */
class EditProcessorRequest extends FormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        /** @var Processor $processor */
        $processor = $this->route('processor');
        return $processor->getConfigValidation();
    }

    /**
     * @return array
     */
    public function getProcessorConfigFields()
    {
        $processor = $this->route('processor');

        $out = [];
        foreach ($processor->getConfigValidation() as $k => $v) {
            $out[$k] = $this->input($k);
        }

        return $out;
    }
}