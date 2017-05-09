<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\FormRequest;
use Illuminate\Http\Request;

/**
 * Class UploadController
 * @package App\Http\Controllers
 */
class UploadController
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        // Figure out which consumer this is.
        $consumer = $request->input('consumer');

        var_dump($_FILES);
        dd($request->files);
        //dd(\Request::allFiles());

        return \Response::json([
            'success' => true,
            'asset' => [
                'id' => 1
            ]
        ]);
    }

}