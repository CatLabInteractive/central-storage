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
class RunProcessor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processor:run {id}';

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
        $processorId = $this->argument('id');

        /** @var Processor $processor */
        $processor = Processor::findOrFail($processorId);
        $processor->setOutput($this->output);

        // Check for pending jobs
        $pendingJobs = $processor->getPendingJobs()->take(10);
        $pendingJobs->each(
            function(ProcessorJob $job) use ($processor) {
                $processor->updateJob($job);
            }
        );

        // Create new jobs
        $assetsToProcess = $processor->getProcessBatch()->take(10);
        $assetsToProcess->each(
            function(ConsumerAsset $asset) use ($processor) {
                $processor->process($asset);
            }
        );
    }
}