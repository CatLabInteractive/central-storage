<?php

return [

    'userClassName' => \App\Models\User::class,
    'assetClassName' => \App\Models\Asset::class,
    'pathGenerator' => \CatLab\Assets\Laravel\PathGenerators\GroupedRandomPrefixPathGenerator::class,

    'route' => false,

    'disk' => env('ASSETS_DISK', 'local'),

    's3' => [
        'cloudfront' => env('AWS_CLOUDFRONT'),
        'redirect' => env('AWS_REDIRECT') == 'true' || env('AWS_REDIRECT') == 1,
    ],

    // Maximum upload size for image files in bytes. Default is 20MB.
    // Videos and other file types are not affected by this limit.
    'max_image_file_size' => env('MAX_IMAGE_FILE_SIZE', 20 * 1024 * 1024),

];