<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(
    [
        'middleware' => [ 'auth.api' ],
        'namespace' => '\Api'
    ],
    function()
    {
        Route::post('/v1/upload', 'UploadController@upload');
    }
);

/*
Route::middleware('auth.api')->get('/user', function (Request $request) {
    return $request->user();
});
*/