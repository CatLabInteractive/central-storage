<?php

namespace App\Http\Requests;

/**
 * Class FormRequest
 * @package App\Http\Requests
 */
class FormRequest extends \Illuminate\Foundation\Http\FormRequest
{
    /**
     * @return bool
     */
    public function authorize()
    {
        // Authorization is handled in controllers.
        return true;
    }
}