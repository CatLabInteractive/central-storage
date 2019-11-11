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
Route::get('/v1/healthcheck', 'Api\HealthCheckController@healthCheck');

Route::group(
    [
        'middleware' => [ 'auth.api' ],
        'namespace' => '\App\Http\Controllers\Api'
    ],
    function()
    {
        Route::post('/v1/upload', 'UploadController@upload');
        Route::delete('/v1/assets/{assetId}', 'UploadController@remove');
    }
);

/*
Route::middleware('auth.api')->get('/user', function (Request $request) {
    return $request->user();
});
*/