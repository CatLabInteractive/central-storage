<?php

namespace App\Http\Requests;
use App\Models\Processor;
use Illuminate\Validation\Rule;

/**
 * Class CreateProcessorRequest
 * @package App\Http\Requests
 */
class CreateProcessorRequest extends FormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        $consumer = $this->route('consumer');

        $validProcessors = array_map(
            function($v) {
                return class_basename($v);
            },
            Processor::getProcessors()
        );

        return [
            'processor' => [
                'required',
                Rule::in($validProcessors)
            ],
            'variation_name' => [
                'required',
                Rule::unique('processors')->where(function($query) use ($consumer) {
                    $query->where('consumer_id', '=', $consumer->id);
                }),
                Rule::notIn([ 'original' ])
            ],
            'trigger_mimetype' => 'required',
        ];
    }

    /**
     * @return string
     */
    public function getProcessorClassName()
    {
        return 'App\Processors' . $this->input('processor');
    }
}