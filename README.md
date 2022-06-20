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

Setup the database:

```php artisan migrate```

Create a user (for security, there is no registration form enabled in the website)

```php artisan user:create```

Done! You should now be able to login on the website.

### Disk configuration
Central Storage uses the default File Storage systems, so you can configure where files will be stored. 
The files are stored in various folders based on a hashing mechanism that should in theory improve performance 
when reading a lot of sequentially uploaded files.

Note that certain Processors (like the Elastic Transcoder processor) requires the files to be stored on Amazon S3.

### Setting up a consumer
A 'consumer' is an application that will use central storage for storing files. 

Navigate to `Consumers` and click `Create consumer`.
Fill in a descriptive name.

You should now get your consumer key and consumer secret.

### Setting up your client project (in php)
Central Storage provides a standard REST API and is thus consumable by any language or framework. We will focus on the 
PHP client here:

In your PHP project, run 
```composer require catlabinteractive/central-storage-client```

Then, wherever you want to upload a file, initialize the client:

```php
$centralStorageClient = new CentralStorageClient(
    'https://your-central-storage-url.com',
    'your_key',
    'your_secret'
);
```

If you are using Laravel, you can instead use the provider:
```php
    'providers' => [
    
        [...]
        
        CatLab\CentralStorage\Client\CentralStorageServiceProvider::class,
    
    ],
    
    'aliases' => [
    
        [...],
        
        'CentralStorage' => CatLab\CentralStorage\Client\CentralStorageClientFacade::class,
    
    ]
]
```

The PHP client consumes Symfony's File objects. That means you can upload files straight from Laravel:

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
        $this->authorizeCreate($request);

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