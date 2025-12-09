<?php

namespace App\Processors;

use App\Http\Requests\CreateProcessorRequest;
use App\Models\Asset;
use App\Models\Consumer;
use App\Models\ConsumerAsset;
use App\Models\Processor;
use App\Models\ProcessorJob;
use App\Models\ProcessorTrigger;
use Aws\Exception\AwsException;
use Aws\MediaConvert\MediaConvertClient;
use Aws\Sns\Exception\InvalidSnsMessageException;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use League\Flysystem\FileNotFoundException;
use Cache;

class AwsMediaConvert extends Processor
{
    // AWS MediaConvert API version
    const AWS_VERSION_MEDIACONVERT = '2017-08-29';

    /**
     * @var MediaConvertClient
     */
    private static $awsClients;

    private static function getAwsClient(string $region, string $key, string $secret, string $endpoint = null)
    {
        $cacheKey = implode('-', [ $region, $key,  $secret ]);
        if (!isset(self::$awsClients[$cacheKey])) {

            $baseClient = new MediaConvertClient([
                'region' => $region,
                'version' => self::AWS_VERSION_MEDIACONVERT,
                'credentials' => [
                    'key' => $key,
                    'secret' => $secret
                ],
                'retries' => 5,
            ]);

            // Cache key / TTL for discovered endpoint
            $endpointCacheKey = 'mediaconvert_endpoint_' . md5($region . '|' . $key);
            $endpointTtl = config('services.mediaconvert.endpoint_ttl', 60 * 60 * 24); // default 24h

            if (!$endpoint) {
                // Try cached endpoint first
                $cached = Cache::get($endpointCacheKey);
                if ($cached) {
                    $endpoint = $cached;
                } else {
                    try {
                        $describe = $baseClient->describeEndpoints(['MaxResults' => 1])->toArray();
                        if (!empty($describe['Endpoints'][0]['Url'])) {
                            $endpoint = $describe['Endpoints'][0]['Url'];
                            Cache::put($endpointCacheKey, $endpoint, $endpointTtl);
                        }
                    } catch (AwsException $e) {
                        \Log::error('MediaConvert describeEndpoints failed: ' . $e->getMessage());
                        report($e);
                        // don't throw here — let client be created without discovered endpoint if possible
                    }
                }
            }

            $client = new MediaConvertClient([
                'region' => $region,
                'version' => self::AWS_VERSION_MEDIACONVERT,
                'credentials' => [
                    'key' => $key,
                    'secret' => $secret
                ],
                'endpoint' => $endpoint ?: null,
                'retries' => 5,
            ]);

            self::$awsClients[$cacheKey] = $client;
        }

        return self::$awsClients[$cacheKey];
    }

    /**
     * @return array
     */
    public function getConfigValidation()
    {
        return [
            // AWS auth
            'key' => 'required',
            'secret' => 'required',
            'region' => 'required',

            // MediaConvert specifics
            'role' => 'required|string',        // IAM role ARN for MediaConvert (e.g. MediaConvert_Default_Role)
            'queue' => 'nullable|string', // Optional queue ARN

            'endpoint' => 'nullable|string', // Optional custom endpoint; if omitted we'll discover it

            // Processing
            'presets' => 'required',      // Comma-separated preset names or ARNs (aligned with extensions)
            'extensions' => 'required',   // Comma-separated extensions aligned with presets (e.g. mp4,webm)
            'mimetype' => 'required',
            'timeSpanStartTime' => 'nullable|numeric',
            'timeSpanDuration' => 'nullable|numeric',
        ];
    }

    /**
     * Create via UI (unchanged from stub)
     * @param Consumer $consumer
     * @param CreateProcessorRequest $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function processCreate(Consumer $consumer, CreateProcessorRequest $request)
    {
        $this->authorize('create', [ $consumer, Processor::class ]);

        $processor = new Processor([
            'processor' => $request->input('processor'),
            'variation_name' => $request->input('variation_name'),
            'default_variation'  => $request->input('default_variation') ? true : false
        ]);

        $consumer->processors()->save($processor);

        // also add the trigger
        $processorTrigger = new ProcessorTrigger([
            'mimetype' => $request->input('trigger_mimetype')
        ]);

        $processor->triggers()->save($processorTrigger);

        return redirect(action('ProcessorController@edit', [ $consumer->id, $processor->id ]));
    }

    /**
     * SNS notification endpoint (MediaConvert via EventBridge -> SNS)
     * Note: Called on an EMPTY processor; we identify jobs by external id.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public static function notify(\Illuminate\Http\Request $request)
    {
        // Instantiate the Message and Validator
        $message = Message::fromRawPostData();
        $validator = new MessageValidator();

        try {
            $validator->validate($message);
        } catch (InvalidSnsMessageException $e) {
            http_response_code(404);
            \Log::error('SNS Message Validation Error: ' . $e->getMessage());
            return \Response::json([
                'error' => [ 'message' => 'SNS Message Validation Error' ]
            ], 404);
        }

        // Handle subscription
        if ($message['Type'] === 'SubscriptionConfirmation') {
            @file_get_contents($message['SubscribeURL']);
            return;
        }

        if (!isset($message['Message'])) {
            \Log::error('SNS Message not understood (no Message).');
            return \Response::json([
                'error' => [ 'message' => 'Job not found.' ]
            ], 404);
        }

        $messageData = json_decode($message['Message'], true);
        \Log::info('MediaConvert SNS Message: ' . print_r($messageData, true));

        // MediaConvert events typically provide detail.jobId
        $jobId = $messageData['detail']['jobId'] ?? ($messageData['jobId'] ?? null);
        if (!$jobId) {
            \Log::error('SNS Message has no MediaConvert jobId.');
            return \Response::json([
                'error' => [ 'message' => 'Job not found.' ]
            ], 404);
        }

        $jobs = self::getJobsByExternalId($jobId)->get();
        if ($jobs->count() === 0) {
            return \Response::json([
                'error' => [ 'message' => 'Job not found.' ]
            ], 404);
        }

        \Log::info('Found ' . $jobs->count() . ' jobs for MediaConvert jobId ' . $jobId);

        $jobs->each(function(ProcessorJob $job) {
            /** @var Processor $processor */
            $processor = $job->processor;
            $processor->updateJob($job);
        });
    }

    /**
     * Submit job to MediaConvert
     * @param ProcessorJob $job
     * @param string $outputPath Prefix (key) in bucket where outputs should go
     */
    public function handle(ProcessorJob $job, $outputPath)
    {
        /** @var Asset $sourceAsset */
        $sourceAsset = $job->consumerAsset->getAsset();

        $presets = $this->getPresets();
        $this->output->writeln('Presets: ' . implode(', ', $presets));

        $client = $this->getMediaConvertClient();

        // Resolve S3 bucket for input/output from asset disk config
        $diskName = $sourceAsset->disk;
        $bucket = config('filesystems.disks.' . $diskName . '.bucket');
        if (!$bucket) {
            throw new \RuntimeException('Cannot resolve S3 bucket for disk: ' . $diskName);
        }

        $inputKey = ltrim($sourceAsset->path, '/');
        $inputS3 = 's3://' . $bucket . '/' . $inputKey;

        $outputPrefix = trim($outputPath, '/') . '/';
        $destinationS3 = 's3://' . $bucket . '/' . $outputPrefix;

        // Build Inputs with optional clipping
        $input = [
            'FileInput' => $inputS3,

            'VideoSelector' => [
                'Rotate' => 'AUTO'
            ],

            // Ensure at least one audio selector exists so selector_sequence_id 0 is valid
            'AudioSelectors' => [
                // name can be arbitrary; MediaConvert will assign selector_sequence_id based on order
                'Audio Selector 1' => [
                    'DefaultSelection' => 'DEFAULT'
                ]
            ]
        ];
        $start = $this->getConfig('timeSpanStartTime');
        $duration = $this->getConfig('timeSpanDuration');
        if ($start || $duration) {
            // Convert seconds to ZEROBASED timecode HH:MM:SS:FF (FF=00)
            $toTc = function($seconds) {
                $seconds = (int) floor($seconds);
                $h = str_pad((string) floor($seconds / 3600), 2, '0', STR_PAD_LEFT);
                $m = str_pad((string) floor(($seconds % 3600) / 60), 2, '0', STR_PAD_LEFT);
                $s = str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT);
                return $h . ':' . $m . ':' . $s . ':00';
            };

            $clip = [];
            if ($start) { $clip['StartTimecode'] = $toTc($start); }
            if ($duration) { $clip['EndTimecode'] = $toTc(($start ?? 0) + $duration); }
            if (!empty($clip)) {
                $input['InputClippings'] = [ $clip ];
                $input['TimecodeSource'] = 'ZEROBASED';
            }
        }

        // Build outputs using FILE_GROUP (one file per preset)
        $outputs = [];
        foreach ($presets as $i => $preset) {

            $nameModifier = '-' . $i . '.' . $this->getExtension($i);

            $outputs[] = [
                'Preset' => $preset, // Name or ARN
                'NameModifier' => $nameModifier,
            ];
        }

        $jobSettings = [
            'Inputs' => [ $input ],
            'OutputGroups' => [
                [
                    'Name' => 'File Group',
                    'OutputGroupSettings' => [
                        'Type' => 'FILE_GROUP_SETTINGS',
                        'FileGroupSettings' => [ 'Destination' => $destinationS3 ]
                    ],
                    'Outputs' => $outputs
                ]
            ],
        ];

        $params = [
            'Role' => $this->getConfig('role'),
            'Settings' => $jobSettings
        ];

        $queue = $this->getConfig('queue');
        if ($queue) { $params['Queue'] = $queue; }

        $result = $client->createJob($params)->toArray();
        $jobData = $result['Job'];

        $job->setExternalId($jobData['Id']);
        $job->setState(ProcessorJob::STATE_PENDING);
    }

    /**
     * Poll job and create variations on completion
     * @param ProcessorJob $job
     */
    protected function handleUpdate(ProcessorJob $job)
    {
        if ($job->isFinished()) {
            return;
        }

        $this->output->writeln('Updating MediaConvert job ' . $job->id);

        $jobId = $job->external_id;
        $client = $this->getMediaConvertClient();

        $response = $client->getJob([ 'Id' => $jobId ])->toArray();
        $jobData = $response['Job'];


        \Log::info('MediaConvert getJob: ' . print_r($jobData, true));

        $status = $jobData['Status'] ?? null; // SUBMITTED | PROGRESSING | COMPLETE | CANCELED | ERROR
        if ($status === 'ERROR' || $status === 'CANCELED') {
            $detail = ($jobData['ErrorMessage'] ?? 'Unknown error');
            $this->output->writeln('ERROR! ' . $detail);
            $job->setState(ProcessorJob::STATE_FAILED);
            return;
        }

        if ($status !== 'COMPLETE') {
            $this->output->writeln('Not finished yet. Status: ' . $status);
            return;
        }

        // Build a flat list of OutputDetails in the order MediaConvert returned them
        $outputDetailsList = [];
        if (!empty($jobData['OutputGroupDetails']) && is_array($jobData['OutputGroupDetails'])) {
            foreach ($jobData['OutputGroupDetails'] as $og) {
                if (!empty($og['OutputDetails']) && is_array($og['OutputDetails'])) {
                    foreach ($og['OutputDetails'] as $od) {
                        $outputDetailsList[] = $od;
                    }
                }
            }
        }

        // Build output file paths based on naming rules
        /** @var ConsumerAsset $consumerAsset */
        $consumerAsset = $job->consumerAsset;
        /** @var Asset $originalAsset */
        $originalAsset = $consumerAsset->getAsset();

        $outputKeyPrefix = $this->getOutputPath($consumerAsset) . '/';

        // Base name without extension
        $originalName = basename($originalAsset->path);
        $dotPos = strrpos($originalName, '.');
        $baseName = $dotPos !== false ? substr($originalName, 0, $dotPos) : $originalName;

        $existingVariationNames = [];
        $presets = $this->getPresets();

        foreach ($presets as $index => $presetId) {
            $variationName = $this->getVariationName($index);
            if (isset($existingVariationNames[$variationName])) {
                continue;
            }

            $newPath = $outputKeyPrefix . $baseName . '-' . $index . '.' . $this->getExtension($index);
            $newAsset = $this->createAsset($consumerAsset, $newPath);

            $od = $outputDetailsList[$index] ?? null;
            if ($od !== null) {
                // Width / Height
                $width = $od['VideoDetails']['WidthInPx'] ?? null;
                $height = $od['VideoDetails']['HeightInPx'] ?? null;

                // File size (MediaConvert may provide FileSize in bytes)
                $size = null;
                if (isset($od['FileSize'])) {
                    $size = (int) $od['FileSize'];
                } elseif (isset($od['FileSizeInBytes'])) {
                    $size = (int) $od['FileSizeInBytes'];
                }

                // Duration: prefer DurationInMs top-level, fall back to VideoDetails
                $duration = null;
                $durationMs = $od['DurationInMs'] ?? ($od['VideoDetails']['DurationInMs'] ?? null);
                if ($durationMs !== null) {
                    $duration = round($durationMs / 1000, 3); // seconds, with millisecond precision
                }

                // If MediaConvert did not provide size, fetch it from S3
                if ($size === null) {
                    try {
                        $size = $newAsset->getDisk()->size($newPath);
                    } catch (FileNotFoundException $e) {
                        \Log::error('Could not fetch size for ' . $newPath . ': ' . $e->getMessage());
                        report($e);

                        $job->setState(ProcessorJob::STATE_FAILED);
                        return;
                    }
                }

                if ($width !== null) { $newAsset->width = (int) $width; }
                if ($height !== null) { $newAsset->height = (int) $height; }
                if ($size !== null) { $newAsset->size = (int) $size; }
                if ($duration !== null) { $newAsset->duration = (float) $duration; }

            }

            $variation = $originalAsset->linkVariationFromJob($this, $variationName, $consumerAsset, $newAsset, false, $job);
            $existingVariationNames[$variationName] = $variation;
        }

        $job->setState(ProcessorJob::STATE_FINISHED);
    }

    /**
     * Variation name logic aligned with ElasticTranscoder
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
        if (array_key_exists($index, $extensions)) {
            return $extensions[$index];
        }
        return $extensions[count($extensions) - 1];
    }

    /**
     * Create or reuse MediaConvert client with endpoint discovery
     * @return MediaConvertClient
     */
    protected function getMediaConvertClient()
    {
        return self::getAwsClient(
            $this->getConfig('region'),
            $this->getConfig('key'),
            $this->getConfig('secret'),
            $this->getConfig('endpoint')
        );
    }

    /**
     * Determine if two MediaConvert processors are similar (same presets and clipping config)
     * @param Processor $processor
     * @return bool
     */
    protected function isConfigSimilar(Processor $processor)
    {
        if (!($processor instanceof AwsMediaConvert)) {
            throw new \LogicException('isConfigSimilar should receive an object of type ' . get_class($this));
        }

        $myPresets = $this->getPresets();
        $theirPresets = $processor->getPresets();
        if ($myPresets != $theirPresets) {
            return false;
        }

        foreach ([ 'timeSpanStartTime', 'timeSpanDuration' ] as $k) {
            if ($this->getConfig($k) != $processor->getConfig($k)) {
                return false;
            }
        }

        return true;
    }
}
