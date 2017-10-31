<?php

Route::get('/assets/combine', 'AssetController@combine');
Route::get('/assets/{id}', 'AssetController@viewConsumerAsset');