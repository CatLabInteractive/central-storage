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

Route::get('/', 'HomeController@welcome');

//Auth::routes();

// Authentication Routes...
Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Auth\LoginController@login');
Route::post('logout', 'Auth\LoginController@logout')->name('logout');

/*
$this->get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
$this->post('register', 'Auth\RegisterController@register');
*/

// Password Reset Routes...
Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
Route::post('password/reset', 'Auth\ResetPasswordController@reset');

Route::get('/home', 'HomeController@home');

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
        Route::get('/consumers/{consumer}/test', 'ConsumerController@test');
        Route::post('/consumers/{consumer}/test', 'ConsumerController@uploadTest');

        Route::get('/consumers/{consumer}/processors', 'ProcessorController@index');
        Route::get('/consumers/{consumer}/processors/create', 'ProcessorController@create');
        Route::post('/consumers/{consumer}/processors/create', 'ProcessorController@processCreate');

        Route::get('/consumers/{consumer}/processors/{processor}', 'ProcessorController@edit');
        Route::post('/consumers/{consumer}/processors/{processor}', 'ProcessorController@processEdit');
        Route::get('/consumers/{consumer}/processors/{processor}/change', 'ProcessorController@setDefault');
        Route::get('/consumers/{consumer}/processors/{processor}/run', 'ProcessorController@run');

        Route::get('/consumers/{consumer}/explore', 'ExplorerController@explore');

    }
);