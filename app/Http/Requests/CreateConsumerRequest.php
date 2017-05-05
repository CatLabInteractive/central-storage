<?php

namespace App\Http\Requests;

/**
 * Class CreateConsumerRequest
 * @package App\Http\Requests
 */
class CreateConsumerRequest extends FormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required'
        ];
    }
}