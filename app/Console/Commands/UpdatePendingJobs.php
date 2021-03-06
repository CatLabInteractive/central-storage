<?php

namespace App\Console\Commands;

use App\Models\ConsumerAsset;
use App\Models\Processor;
use App\Models\ProcessorJob;
use CatLab\Assets\Laravel\Models\Asset;
use File;
use Illuminate\Console\Command;

/**
 * Class MigrateDisk
 * @package App\Console\Commands
 */
class UpdatePendingJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processor:update-jobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a processor';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Check for pending jobs
        Processor::all()->each(function(Processor $processor) {
            $pendingJobs = $processor->getPendingJobs()->take(10);

            if ($pendingJobs->count() > 0) {
                $this->output->writeln('Processor ' . $processor->id . ': Found ' . $pendingJobs->count() . ' pending jobs');
            }

            $pendingJobs->each(
                function(ProcessorJob $job) use ($processor) {
                    $processor->updateJob($job);
                }
            );
        });
    }
}