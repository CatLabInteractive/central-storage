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
    if (Auth::guest()) {
        return view('welcome');
    } else {
        return redirect(action('StatisticsController@index'));
    }
});

//Auth::routes();

// Authentication Routes...
$this->get('login', 'Auth\LoginController@showLoginForm')->name('login');
$this->post('login', 'Auth\LoginController@login');
$this->post('logout', 'Auth\LoginController@logout')->name('logout');

// Password Reset Routes...
$this->get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
$this->post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
$this->get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
$this->post('password/reset', 'Auth\ResetPasswordController@reset');

Route::get('/home', function() {
    return redirect()->action('ConsumerController@index');
});

Route::get('/assets/{id}', 'AssetController@viewConsumerAsset');

Route::group(
    [
        'middleware' => [ 'auth' ]
    ],
    function() {

        Route::get('/statistics', 'StatisticsController@index');

        Route::get('/consumers', 'ConsumerController@index');

        Route::get('/consumers/create', 'ConsumerController@create');
        Route::post('/consumers/create', 'ConsumerController@processCreate');

        Route::get('/consumers/{consumer}', 'ConsumerController@view');

    }
);