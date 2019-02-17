<?php

Route::get('/assets/combine', 'AssetController@combine');

Route::get('/assets/{id}{extension}', 'AssetController@viewConsumerAsset')
    ->where([
        'id' => '[a-zA-Z0-9-_]+',
        'extension' => '\..+'
    ]);

Route::get('/assets/{id}', 'AssetController@viewConsumerAsset');

Route::any('processors/notification/{processorName}', 'ProcessorController@notification');

Route::get('/proxy/{consumerKey}/{urlBase64}/{signature}', 'CachedProxyController@cache');