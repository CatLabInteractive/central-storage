<?php

Route::get('/assets/combine', 'AssetController@combine');

Route::get('/assets/{id}{extension?}/{subPath?}', 'AssetController@viewConsumerAsset')
    ->where([
        'id' => '[a-zA-Z0-9-_]+',
        'extension' => '\.[a-zA-Z0-9]+',
        'subPath' => '(.*)'
    ]);

Route::options('/assets/{id}{extension?}/{subPath?}', 'AssetController@assetOptionsRequest')
    ->where([
        'id' => '[a-zA-Z0-9-_]+',
        'extension' => '\.[a-zA-Z0-9]+',
        'subPath' => '(.*)'
    ]);

Route::get('/assets/{id}/{subPath?}', 'AssetController@viewConsumerAsset')
    ->where([
        'id' => '[a-zA-Z0-9-_]+',
        'extension' => '\.[a-zA-Z0-9]+',
        'subPath' => '(.*)'
    ]);

Route::options('/assets/{id}/{subPath?}', 'AssetController@assetOptionsRequest')
    ->where([
        'id' => '[a-zA-Z0-9-_]+',
        'extension' => '\.[a-zA-Z0-9]+',
        'subPath' => '(.*)'
    ]);

Route::any('processors/notification/{processorName}', 'ProcessorController@notification');

Route::get('/proxy/{consumerKey}/{urlBase64}/{signature}', 'CachedProxyController@cache');
