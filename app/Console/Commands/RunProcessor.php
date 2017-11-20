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

        // Create new jobs
        $assetsToProcess = $processor->getProcessBatch()->take(1000);
        $this->output->writeln('Processing ' . $assetsToProcess->count() . ' assets');

        $assetsToProcess->each(
            function(ConsumerAsset $asset) use ($processor) {
                $processor->process($asset);
            }
        );
    }
}