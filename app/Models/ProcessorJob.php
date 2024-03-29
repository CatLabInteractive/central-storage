<?php

namespace App\Models;

use CatLab\Assets\Laravel\Models\Variation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProcessorJob
 * @package App\Models
 */
class ProcessorJob extends Model
{
    const STATE_PREPARED = 'PREPARED';
    const STATE_PENDING = 'PENDING';
    const STATE_FINISHED = 'FINISHED';
    const STATE_FAILED = 'FAILED';
    const STATE_RESCHEDULED = 'RESCHEDULED';

    /**
     * @var \Illuminate\Contracts\Cache\Lock
     */
    private $cacheLock;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function consumerAsset()
    {
        return $this->belongsTo(ConsumerAsset::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function processor()
    {
        return $this->belongsTo(Processor::class);
    }

    /**
     * @param $state
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @param $identifier
     * @return $this
     */
    public function setExternalId($identifier)
    {
        $this->external_id = $identifier;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFinished()
    {
        switch ($this->state) {
            case ProcessorJob::STATE_FINISHED:
            case ProcessorJob::STATE_FAILED:
                return true;

            case ProcessorJob::STATE_PENDING:
            case ProcessorJob::STATE_PREPARED:
                return false;

            default:
                throw new \InvalidArgumentException('Invalid job state: ' . $this->state);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variations()
    {
        return $this->hasMany(Variation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function originalJob()
    {
        return $this->belongsTo(ProcessorJob::class, 'original_job_id');
    }

    /**
     * @param OutputInterface $output
     * @param Processor $processor
     * @throws \Exception
     */
    public function runAgain(OutputInterface $output, Processor $processor)
    {
        // Delete all created variations
        $this->variations->each(function(Variation $variation) {
            $variation->delete();
        });

        // mark the job as rescheduled
        $this->state = ProcessorJob::STATE_RESCHEDULED;
        $this->save();

        // re-run all consumer assets
        $consumerAsset = $this->consumerAsset;

        $output->writeln('- Running processor again for consumerAsset ' . $consumerAsset->id);
        $processor->process($consumerAsset);
    }

    /**
     * @return bool
     */
    public function lockJob()
    {
        // Make sure we use a lock to prevent other processes from generating the same variation
        $this->cacheLock = Cache::lock('ctlb-processorJob-' . $this->id, 30);
        return $this->cacheLock->get();

        /*
        $result = false;
        \DB::transaction(function() use (&$result) {

            if ($this->locks()->count() > 0) {
                $result = false;
                return;
            }

            $lock = new ProcessorLock();
            $this->locks()->save($lock);

            $result = true;
        });

        return $result;
        */
    }

    /**
     * Unlock the job
     */
    public function unlockJob()
    {
        if ($this->cacheLock) {
            $this->cacheLock->release();
        }
    }
}
