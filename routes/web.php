<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', function() {
    return redirect()->action('ConsumerController@index');
});

Route::get('/assets/{id}', 'AssetController@viewConsumerAsset');

Route::group(
    [
        'middleware' => [ 'auth' ]
    ],
    function() {

        Route::get('/consumers', 'ConsumerController@index');

        Route::get('/consumers/create', 'ConsumerController@create');
        Route::post('/consumers/create', 'ConsumerController@processCreate');

        Route::get('/consumers/{consumer}', 'ConsumerController@view');

    }
);