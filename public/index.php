<?php


$uri = $_SERVER['REQUEST_URI'];
$assetPath = '/assets/';

/**
 * First we do a quick check to see if we have a cached response.
 * If we have a cached response, we don't need to boot up Laravel.
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    substr($uri, 0, strlen('/assets/'))
) {
    require_once '../vendor/catlabinteractive/laravel-assets/src/Laravel/Helpers/ResponseCache.php';

    $cache = new \CatLab\Assets\Laravel\Helpers\ResponseCache(__DIR__ . '/../storage');
    $cache->outputIfExists();
}

/**
 * No cached response found? Okay then, let's go to Laravel world.
 */
require_once 'laravel.php';