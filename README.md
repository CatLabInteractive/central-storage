## Central Storage

Central Storage is a storage engine built in Laravel. It includes duplicate upload detection, supports on the fly image
(but cached) image resize and allows you to set 'Processors' that handle more complex file transformations like video
transcoding etc.

### Quick start
CHeckout this code:

```git clone git@github.com:CatLabInteractive/central-storage.git```

Let composer install all dependencies:

```composer install```

Copy `.env.example` to `.env` and set the database credentials.

Set a unique app key:

```php artisan key:generate```

Setup the database:

```php artisan migrate```

Create a user (for security, there is no registration form enabled in the website)

```php artisan user:create```

Done! You should now be able to login on the website.

### Disk configuration
Central Storage uses the default File Storage systems, so you can configure where files will be stored.
The files are stored in various folders based on a hashing mechanism that should in theory improve performance
when reading a lot of sequentially uploaded files.

Note that certain Processors (like the AWS MediaConvert processor, formerly Elastic Transcoder) requires the files to be stored on Amazon S3.

### Setting up a consumer
A 'consumer' is an application that will use central storage for storing files.

Navigate to `Consumers` and click `Create consumer`.
Fill in a descriptive name.

You should now get your consumer key and consumer secret.

### Upload limits
Image uploads are limited to 20MB by default. This limit only applies to image files (`image/*`); videos and 
other file types are not affected. The limit can be configured via the `MAX_IMAGE_FILE_SIZE` environment variable
(value in bytes).

```MAX_IMAGE_FILE_SIZE=20971520```

The PHP-level upload limits (`upload_max_filesize` and `post_max_size`) are set to 1GB in `docker/php/upload.ini`.

### File resize
In order for file resizing to work, you need to either install the GD or the Imagick php extensions. In your .env file,
set ```INTERVENTION_DRIVER=imagick``` accordingly. The project uses the [Intervention Image](https://github.com/Intervention/image)
library to manipulate images, so for further install instructions, take a look there.

### Content delivery network
In order to use a CDN with Central Storage (like Amazon Cloudfront), point the origin of the network to your asset website
and configure your Central Storage Client projects `FRONT` config parameter to the CDN network. This way, uploads will 
use the direct connection to Central Storage, but all generated links fetch content will use the CDN.

## Setting up your client project (in Laravel)
Central Storage provides a standard REST API and is thus consumable by any language or framework. We will focus on the
existing Laravel client here. Note that it is a trivial task to implement a new client, as there is only a few methods
to implement.

In your Laravel project, run
```composer require catlabinteractive/central-storage-client```

Then, wherever you want to upload a file, initialize the client:

```php
$centralStorageClient = new CentralStorageClient(
    'https://your-central-storage-url.com',
    'your_key',
    'your_secret'
);
```

Or, if you like, you can use the provider that uses the default configuration files:
```php
    'providers' => [
    
        [...]
        
        CatLab\CentralStorage\Client\CentralStorageServiceProvider::class,
    
    ],
    
    'aliases' => [
    
        [...]
        
        'CentralStorage' => CatLab\CentralStorage\Client\CentralStorageClientFacade::class,
    
    ]
]
```

The PHP client consumes Symfony's File objects. That means you can upload files straight from Laravel. The client returns
an Eloquent model 'Asset', which can be saved directly to a database (migration file is available in `central-storage-client/database`).

```php
<?php

use App\Models\Attachments\Asset;
use Illuminate\Http\Request;

class AssetController
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $file = $request->file()->first();
        if (!$file) {
            abort(400, 'Please provide a valid file.');
        }

        if (!$file->isValid()) {
            abort(400, 'File not valid: ' . $file->getErrorMessage());
        }

        /** @var Asset $asset */
        $asset = \CentralStorage::store($file);
        $asset->save();

        return response()->json($asset->getData());
    }
}
```

For further instructions on how to upload and consume assets, please check out the
[Central Storage Client](https://github.com/catlabinteractive/central-storage-client) documentation.
