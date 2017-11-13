<?php

return [

    'userClassName' => \App\Models\User::class,
    'assetClassName' => \App\Models\Asset::class,
    'pathGenerator' => \CatLab\Assets\Laravel\PathGenerators\GroupedIdPathGenerator::class,

    'route' => false,

    'disk' => env('ASSETS_DISK', 'local'),

];