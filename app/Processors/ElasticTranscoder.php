<?php

namespace App\Processors;

use App\Models\Asset;
use App\Models\ConsumerAsset;
use App\Models\Processor;
use App\Models\ProcessorJob;
use Aws\ElasticTranscoder\ElasticTranscoderClient;
use Aws\Sns\Exception\InvalidSnsMessageException;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Request;

/**
 * Class ElasticTranscoder
 * @package App\Processors
 */
class ElasticTranscoder extends Processor
{
    const AWS_VERSION_ELASTICTRANSCODER = '2012-09-25';

    /**
     * Called when an external processor tries to notify about job progress.
     * Note that this is called on an EMPTY processor (no id set yet)
     * So first job should be to find the right processor.
     * @param $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public static function notify(\Illuminate\Http\Request $request)
    {
        // Instantiate the Message and Validator
        $message = Message::fromRawPostData();
        $validator = new MessageValidator();

        // Validate the message and log errors if invalid.
        try {
            $validator->validate($message);
        } catch (InvalidSnsMessageException $e) {
            // Pretend we're not here if the message is invalid.
            http_response_code(404);
            \Log::error('SNS Message Validation Error: ' . $e->getMessage());

            return \Response::json([
                'error' => [
                    'message' => 'SNS Message Validation Error'
                ]
            ], 404);
        }

        // Check the type of the message and handle the subscription.
        if ($message['Type'] === 'SubscriptionConfirmation') {
            // Confirm the subscription by sending a GET request to the SubscribeURL
            file_get_contents($message['SubscribeURL']);
            return;
        }

        if (!isset($message['Message'])) {
            \Log::error('SNS Message not understood.');
            return \Response::json([
                'error' => [
                    'message' => 'Job not found.'
                ]
            ], 404);
        }

        $messageData = json_decode($message['Message'], true);

        $jobs = self::getJobsByExternalId($messageData['jobId']);
        if ($jobs->count() === 0) {
            return \Response::json([
                'error' => [
                    'message' => 'Job not found.'
                ]
            ], 404);
        }

        $jobs->each(
            function(ProcessorJob $job) {
                /** @var Processor $processor */
                $processor = $job->processor;
                $processor->updateJob($job);
            }
        );
    }

    /**
     * @var ElasticTranscoderClient
     */
    private $awsClient;

    /**
     * @return array
     */
    public function getConfigValidation()
    {
        return [
            'pipeline' => 'required',
            'key' => 'required',
            'secret' => 'required',
            'region' => 'required',
            'presets' => 'required',
            'extensions' => 'required',
            'mimetype' => 'required',
        ];
    }

    /**
     * @param ProcessorJob $job
     * @param string $outputPath
     */
    public function handle(ProcessorJob $job, $outputPath)
    {
        $asset = $job->consumerAsset->getAsset();

        $pipeline = $this->getConfig('pipeline');
        $segment_duration = $this->getConfig('segment_duration');
        $presets = $this->getPresets();
        $this->output->writeln('Presets: ' . implode(', ', $presets));

        $client = $this->getTranscoderClient();

        // start!
        $result = $this->create_hls_job(
            $client,
            $pipeline,
            $asset->path,
            $outputPath,
            $presets,
            $segment_duration
        );

        $job->setExternalId($result['Id']);
        $job->setState(ProcessorJob::STATE_PENDING);
    }

    /**
     * @param ProcessorJob $job
     */
    public function handleUpdate(ProcessorJob $job)
    {
        // job already finished? no need to update.
        if ($job->isFinished()) {
            return;
        }

        $this->output->writeln('Updating job ' . $job->id);

        $jobId = $job->external_id;

        /** @var ConsumerAsset $asset */
        $consumerAsset = $job->consumerAsset;

        /** @var Asset $asset */
        $asset = $consumerAsset->getAsset();

        $client = $this->getTranscoderClient();
        $jobData = $client->readJob([
            'Id' => $jobId
        ])->toArray();

        $jobData = $jobData['Job'];

        // print_r($jobData);

        // Is error?
        if ($jobData['Status'] === 'Error') {
            if (!isset($jobData['Outputs'])) {
                $this->output->writeln('ERROR! Unknown.');
            } else {
                foreach ($jobData['Outputs'] as $output) {
                    if ($output['Status'] === 'Error') {
                        $this->output->writeln('ERROR! ' . $output['StatusDetail']);
                    }
                }
            }

            $job->setState(ProcessorJob::STATE_FAILED);
            return;
        }

        // Is complete?
        if ($jobData['Status'] !== 'Complete') {
            $this->output->writeln('Not finished yet.');
            return;
        }

        $outputKeyPrefix = $jobData['OutputKeyPrefix'];
        $outputs = $jobData['Outputs'];

        $index = 0;
        foreach ($outputs as $output) {

            $newPath = $outputKeyPrefix . $output['Key'];
            $newAsset = $this->createAsset($consumerAsset, $newPath);

            if (isset($output['Width'])) {
                $newAsset->width = $output['Width'];
            }

            if (isset($output['Height'])) {
                $newAsset->height = $output['Height'];
            }

            if (isset($output['FileSize'])) {
                $newAsset->size = $output['Height'];
            }

            if (isset($output['DurationMillis'])) {
                $newAsset->duration = $output['DurationMillis'] / 1000;
            } elseif (isset($output['Duration'])) {
                $newAsset->duration = $output['Duration'];
            }

            $variationName = $this->getVariationName($index);
            $asset->linkVariation($variationName, $newAsset, $job);

            $index ++;
        }

        // finished.
        $job->setState(ProcessorJob::STATE_FINISHED);
    }

    /**
     * @param $index
     * @return string
     */
    protected function getVariationName($index)
    {
        if (count($this->getPresets()) > 1) {
            return $this->variation_name . '-' . $index;
        } else {
            return $this->variation_name;
        }
    }

    /**
     * @return array
     */
    protected function getPresets()
    {
        $presets = $this->getConfig('presets');
        return array_map('trim', explode(',', $presets));
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $presets = $this->getConfig('extensions');
        return array_map('trim', explode(',', $presets));
    }

    /**
     * @param $index
     * @return mixed
     */
    protected function getExtension($index)
    {
        $extensions = $this->getExtensions();
        if (count($extensions) < $index) {
            return $extensions[$index];
        }
        return $extensions[count($extensions) - 1];
    }

    /**
     * @return ElasticTranscoderClient
     */
    protected function getTranscoderClient()
    {
        if (!isset($this->awsClient)) {
            $this->awsClient = new ElasticTranscoderClient([
                'region' => $this->getConfig('region'),
                'default_caching_config' => sys_get_temp_dir(),
                'version' => self::AWS_VERSION_ELASTICTRANSCODER,
                'credentials' => [
                    'key' => $this->getConfig('key'),
                    'secret' => $this->getConfig('secret')
                ]
            ]);
        }

        return $this->awsClient;
    }

    /**
     * Stolen from HtlJobCreationSample
     * @param $transcoder_client
     * @param $pipeline_id
     * @param $input_key
     * @param $output_key_prefix
     * @param $hls_presets
     * @return mixed
     */
    protected function create_hls_job(
        ElasticTranscoderClient $transcoder_client,
        $pipeline_id,
        $input_key,
        $output_key_prefix,
        $hls_presets
    ) {
        # Setup the job input using the provided input key.
        $input = array('Key' => $input_key);

        # Specify the outputs based on the hls presets array spefified.
        $outputs = array();
        foreach ($hls_presets as $index => $preset_id) {
            $outputs[] = [
                'Key' => "-" . $index . "." . $this->getExtension($index),
                'PresetId' => $preset_id
            ];
        }

        # Create the job.
        $create_job_request = array(
            'PipelineId' => $pipeline_id,
            'Input' => $input,
            'Outputs' => $outputs,
            'OutputKeyPrefix' => $output_key_prefix
        );

        $create_job_result = $transcoder_client->createJob($create_job_request)->toArray();
        return $job = $create_job_result['Job'];
    }

    /**
     * @param Processor $processor
     * @return bool
     */
    protected function isConfigSimilar(Processor $processor)
    {
        if (!($processor instanceof ElasticTranscoder)) {
            throw new \LogicException("isConfigSimilar should always receive an object of type " . get_class($this));
        }

        $myPresets = $this->getPresets();
        $theirPresets = $processor->getPresets();

        return $myPresets == $theirPresets;
    }
}