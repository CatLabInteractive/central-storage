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
    protected $signature = 'processor:run-again {id} {--since=}';

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

        $since = $this->option('since');
        if ($since) {
            $since = Carbon::parse($since);
            if (!$since) {
                $this->output->error('Could not parse since parameter');
                return;
            }
        }

        $jobs = $processor
            ->jobs()
            ->where('state', '=', ProcessorJob::STATE_FINISHED);

        $this->output->writeln('Processor executed ' . $jobs->count() . ' jobs in total.');

        if ($since) {
            $jobs = $jobs->whereDate('created_at', '>=', $since);
            $this->output->writeln('We will run ' . $jobs->count() . ' jobs again.');
        } else {
            $this->output->writeln('We will run all of these jobs again.');
        }

        if (!$this->confirm('Are you sure you want to continue?')) {
            $this->output->writeln('Alright, I\'ll be here if you need me :-)');
            return;
        }

        $jobs->each(function(ProcessorJob $job) use ($processor) {
            $this->output->writeln('Executing job again: ' . $job->id);

            $job->runAgain($this->output, $processor);
        });
    }
}
