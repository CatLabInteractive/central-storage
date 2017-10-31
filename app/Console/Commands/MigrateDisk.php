<?php

namespace App\Console\Commands;

use CatLab\Assets\Laravel\Models\Asset;
use Illuminate\Console\Command;

/**
 * Class MigrateDisk
 * @package App\Console\Commands
 */
class MigrateDisk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'disk:migrate {from} {to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate all assets from one disk to another.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $from = $this->argument('from');
        $to = $this->argument('to');

        $this->output->comment('Migrating assets from ' . $from . ' to ' . $to);

        // Find all assets on specified disk
        $assets = Asset::where('disk', '=', $from);
        $assets->each(function(Asset $asset) use ($to) {
            $this->migrateAsset($asset, $to);
        });
    }

    /**
     * @param Asset $asset
     * @param $to
     */
    protected function migrateAsset(Asset $asset, $to)
    {
        $this->output->writeln('Migrating assets ' . $asset->id . ': ' . $asset->name);

        $asset->moveToDisk($to);
    }
}