<?php

namespace App\Console;

use App\Console\Commands\AssetCacheClear;
use App\Console\Commands\MigrateDisk;
use App\Console\Commands\RunProcessor;
use App\Console\Commands\RunProcessorAgain;
use App\Console\Commands\UpdatePendingJobs;
use CatLab\Assets\Laravel\Commands\CleanupUnusedVariations;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        MigrateDisk::class,
        AssetCacheClear::class,
        RunProcessor::class,
        UpdatePendingJobs::class,
        CleanupUnusedVariations::class,
        RunProcessorAgain::class
    ];

    /**
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
