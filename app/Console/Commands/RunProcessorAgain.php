<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\ConsumerAsset;
use App\Models\Processor;
use App\Models\ProcessorJob;
use App\Models\Variation;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RunProcessorAgain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processor:run-again {id} {--lastUsed=} {--since=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all variations created by a certain processor and create them again.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $processorId = $this->argument('id');

        /** @var Processor $processor */
        $processor = Processor::findOrFail($processorId);
        $processor->setOutput($this->output);

        $lastUsed = $this->option('lastUsed');
        if ($lastUsed) {
            $lastUsed = Carbon::parse($lastUsed);
            if (!$lastUsed) {
                $this->output->error('Could not parse lastUsed parameter');
                return;
            }
        }

        $since = $this->option('since');
        if ($since) {
            $since = Carbon::parse($lastUsed);
            if (!$since) {
                $this->output->error('Could not parse since parameter');
                return;
            }
        }

        $variations = $processor->variations();
        $this->output->writeln('Processor created ' . $variations->count() . ' variations in total.');

        if ($lastUsed) {
            $variations = $variations
                ->leftJoin('assets', 'variations.variation_asset_id', '=', 'assets.id')
                ->whereDate('assets.last_used_at', '>', $lastUsed);

            $this->output->writeln('We will run ' . $variations->count() . ' processes again.');
        } elseif ($since) {
            $variations = $variations
                ->leftJoin('assets', 'variations.variation_asset_id', '=', 'assets.id')
                ->whereDate('assets.created_at', '>=', $since);

            $this->output->writeln('We will run ' . $variations->count() . ' processes again.');
        } else {
            $this->output->writeln('We will run all of these processes again.');
        }

        if (!$this->confirm('Are you sure you want to continue?')) {
            $this->output->writeln('Alright, I\'ll be here if you need me :-)');
            return;
        }

        $variations->each(function(Variation $variation) use ($processor) {

            $this->output->writeln('Removing variation ' . $variation->id);

            /** @var Asset $asset */
            $asset = $variation->original;

            $job = $variation->processorJob;
            if ($job) {
                $job->state = ProcessorJob::STATE_RESCHEDULED;
                $job->save();
            } else {
                $this->output->error('Variation without job found for variation ' . $variation->id);
            }

            // Delete the original variation
            $variation->delete();

            // re-run all consumer assets
            $consumerAssets = $asset->consumerAssets;

            $consumerAssets->each(function(ConsumerAsset $consumerAsset) use ($processor) {
                $this->output->writeln('- Running processor again for consumerAsset ' . $consumerAsset->id);
                $processor->process($consumerAsset);
            });
        });
    }
}
