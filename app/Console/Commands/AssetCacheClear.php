<?php

namespace App\Console\Commands;

use CatLab\Assets\Laravel\Models\Asset;
use File;
use Illuminate\Console\Command;

/**
 * Class MigrateDisk
 * @package App\Console\Commands
 */
class AssetCacheClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assetcache:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all asset caching.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        File::cleanDirectory(storage_path('/response_cache/'));
        File::cleanDirectory(storage_path('/image_cache/'));
    }
}