<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\ConsumerAsset;
use App\Models\ProcessorJob;
use App\Processors\AwsMediaConvert;
use Tests\TestCase;

class AwsMediaConvertTest extends TestCase
{
    /**
     * Submitting a processor output as input to the same kind of job makes
     * MediaConvert reject the job as a feedback loop. Refuse it before calling AWS.
     */
    public function testHandleRefusesProcessorOutputAsInput()
    {
        $asset = new Asset();
        $asset->path = 'files/abcd/proc/3/my-girl-0.mp3';
        $asset->disk = 'local';

        $consumerAsset = new ConsumerAsset();
        $consumerAsset->setRelation('asset', $asset);

        $job = new ProcessorJob();
        $job->setRelation('consumerAsset', $consumerAsset);

        $processor = new AwsMediaConvert();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/processor output/i');

        $processor->handle($job, 'files/abcd/proc/4/my-girl');
    }
}
