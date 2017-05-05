<?php

namespace App\Http\Controllers\Api;

use Request;

/**
 * Class UploadController
 * @package App\Http\Controllers
 */
class UploadController
{

    /**
     *
     */
    public function upload(Request $request)
    {
        // Figure out which consumer this is.
        $consumer = $request->input('consumer');


    }

}