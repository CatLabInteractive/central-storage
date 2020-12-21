<?php

namespace App\Http\Controllers;

use App\Models\Combination;
use App\Models\Consumer;
use App\Models\ConsumerAsset;
use App\Models\Processor;
use CatLab\Assets\Laravel\Helpers\AssetUploader;
use CatLab\Assets\Laravel\Models\Asset;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use DateInterval;
use DateTime;
use Image;
use Response;

/**
 * Class AssetController
 * @package App\Http\Controllers
 */
class AssetController extends \CatLab\Assets\Laravel\Controllers\AssetController
{
    const VARIATION_ORIGINAL = 'original';

    const SIZE_THUMBNAIL = 'thumbnail';
    const SIZE_ORIGINAL = 'original';
    const SIZE_LOWRES = 'lowres';
    const SIZE_RECTANGLE = 'rectangle';
    const SIZE_DIN = 'din';

    /**
     * View an asset
     * @param Request $request
     * @param $key
     * @param $extension
     * @param string $subPath
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function viewConsumerAsset(Request $request, $key) {
        $extension = $request->route('extension');
        $subPath = $request->route('subPath');

        /** @var ConsumerAsset $consumerAsset */
        $consumerAsset = ConsumerAsset::assetKey($key)->first();
        if (!$consumerAsset) {
            abort(404, 'Asset not found: ' . $key);
        }

        if ($consumerAsset->expires_at && $consumerAsset->expires_at < new DateTime()) {
            abort(404, 'Asset not found: ' . $key);
        }

        /** @var \App\Models\Asset $asset */
        $asset = $consumerAsset->getAsset();

        $consumer = $consumerAsset->consumer;

        return $this->viewConsumerWithAsset($request, $asset, $consumer, $subPath);
    }

    /**
     * @param Request $request
     * @param $key
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function assetOptionsRequest(Request $request, $key)
    {
        $extension = $request->route('extension');
        $subPath = $request->route('subPath');

        /** @var ConsumerAsset $consumerAsset */
        $consumerAsset = ConsumerAsset::assetKey($key)->first();
        if (!$consumerAsset) {
            abort(404, 'Asset not found: ' . $key);
        }

        if ($consumerAsset->expires_at && $consumerAsset->expires_at < new DateTime()) {
            abort(404, 'Asset not found: ' . $key);
        }

        /** @var \App\Models\Asset $asset */
        $asset = $consumerAsset->getAsset();

        $headers = [
            'Allow' => 'GET'
        ];

        $headers = array_merge($headers, $this->getCacheHeaders($asset));
        return response('')
            ->withHeaders($headers);
    }

    /**
     * @param Request $request
     * @param \App\Models\Asset $asset
     * @param Consumer $consumer
     * @param string $subPath
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function viewConsumerWithAsset(
        Request $request,
        \App\Models\Asset $asset,
        Consumer $consumer,
        $subPath = null
    ) {
        // Check processors
        $variationName = $request->query('variation');
        $variation = null;

        if ($variationName && $variationName !== self::VARIATION_ORIGINAL) {
            // Look for the processor with this specific variation name

            /** @var Processor|null $processor */
            $processor = $consumer
                ->processors
                ->where('variation_name', '=', $variationName)
                ->first();

            if ($processor && $processor->isTriggered($asset)) {
                //$variationName = $processor->getDesiredVariation($request);
                $variation = $processor->getDesiredVariation($asset, $request, $subPath);
            } else {
                // Invalid variationname? Then set to null.
                $variationName = null;
            }
        }

        // no variation name found? Look for a default one
        if (empty($variationName)) {
            // do we have a "default variation"?
            $processors = $consumer->processors;
            foreach ($processors as $processor) {
                /** @var Processor $processor */
                if ($processor->isDefaultVariation($asset)) {
                    //$variationName = $processor->getDesiredVariation($request);
                    $variation = $processor->getDesiredVariation($asset, $request, $subPath);
                    break;
                }
            }
        }

        // no variation requested? go to plain asset.
        if ($variation === null) {
            return $this->viewAsset($asset);
        } elseif ($variation instanceof \Symfony\Component\HttpFoundation\Response) {
            return $variation;
        } else {
            if ($variation) {
                return $this->viewAsset($variation->asset);
            } else {

                // Variation not found. Check if we are processing this.
                $isProcessing = $asset->isVariationProcessing($variationName, $consumer);

                if ($isProcessing) {
                    return Response::json([
                        'error' => ['message' => 'Asset variation "' . $variationName . '" is still being processed.']
                    ], 202);
                } else {
                    // Not processing? return the original.
                    return $this->viewAsset($asset);
                }
            }
        }
    }

    /**
     * @return array
     */
    protected function getImageSize(Asset $asset)
    {
        $size = \Request::get('size');

        switch ($size) {
            case self::SIZE_DIN:
                $width = 350;
                return [ intval($width), ceil($width * 1.414), true ];
                break;

            case self::SIZE_RECTANGLE:
                $size = 230;
                return [ intval($size), ceil($size / (4/3)), true ];
                break;

            default:
                $targetHeight = $this->getTargetHeight($size);

                if ($targetHeight) {

                    $factor = $targetHeight / $asset->height;

                    $targetWidth = ceil($factor * $asset->width);
                    $targetHeight = ceil($factor * $asset->height);

                    return [ $targetWidth, $targetHeight ];

                }

                break;
        }

        return parent::getImageSize($asset);
    }

    /**
     * @param string $size
     * @return int|null
     */
    public function getTargetHeight($size)
    {
        switch ($size) {
            case self::SIZE_THUMBNAIL:
                return 100;

            case self::SIZE_LOWRES:
                return 1000;

            default:
                return null;
        }
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function combine(
        Request $request
    ) {
        $assetsKeys = $request->query('assets');
        if (empty($assetsKeys)) {
            abort(404, 'No assets provided.');
        }

        // Checksum = just the query string
        $queryPath = $_SERVER['REQUEST_URI'];
        $hash = mb_strlen($queryPath) . 's:' .md5($queryPath);

        // Check for existing combination
        $combinations = Combination::where('hash', '=', $hash)->get();
        foreach ($combinations as $combination) {
            // YOU NEVER KNOW FOR SURE OKAY?!
            // MD5 collision COULD happen.
            if ($combination->path === $queryPath) {
                return $this->getAssetResponse($combination->asset);
            }
        }

        // Generate a new combination.

        /** @var string[] $assets */
        $assetsKeys = explode(',', $assetsKeys);

        // Look for all these assets
        $consumerAssets = [];
        foreach ($assetsKeys as $assetKey) {
            $consumerAsset = ConsumerAsset::assetKey($assetKey)->first();
            if (!$consumerAsset) {
                abort(404, 'Asset ' . $assetKey . ' not found.');
            }

            $consumerAssets[] = $consumerAsset;
        }

        /** @var Asset $firstAsset */
        $firstAsset = $consumerAssets[0]->asset;

        $cols = $request->input('cols', 2);
        $combination = $this->generateCombination($consumerAssets, $cols);

        // encode image data only if image is not encoded yet
        $combination = $combination->encoded ? $combination->encoded : (string) $combination->encode();

        // put in temporary file
        $tmpFile = tempnam(sys_get_temp_dir(), 'asset');
        file_put_contents($tmpFile, $combination);

        // create a new asset with this combination.
        $file = new UploadedFile($tmpFile, 'combination_' . $hash . '.' . $firstAsset->getExtension());
        $uploader = new AssetUploader();

        // Look for duplicate file
        $asset = $uploader->getDuplicate($file);
        if (!$asset) {
            $asset = $uploader->uploadFile($file);
        }

        // create combination
        $combination = new Combination([
            'hash' => $hash,
            'path' => $queryPath
        ]);
        $combination->asset()->associate($asset);

        try {
            $combination->save();
        } catch (QueryException $e) {
            // Probably a duplicate record due to race conditions.
            // Not really a problem, but log it anyway
            \Log::error($e->getMessage());
        }

        return $this->getAssetResponse($combination->asset);
    }

    /**
     * Generate a combination of provided consumer assets.
     * @param ConsumerAsset[] $consumerAssets
     * @param int $columns
     * @return \Intervention\Image\Image
     */
    private function generateCombination($consumerAssets, $columns = 2)
    {
        if (count($consumerAssets) > 64) {
            abort(400, 'A maximum of 64 images can be combined.');
        }

        if (count($consumerAssets) === 0) {
            abort(400, 'No assets found.');
        }

        $width = 0;
        $height = 0;

        $images = [];
        $rowHeight = 0;
        $rowWidth = 0;
        $colCount = 0;

        $firstAsset = $consumerAssets[0]->asset;

        $tmpFiles = [];

        // Now get the individual responses and combine.
        foreach ($consumerAssets as $consumerAsset) {
            /** @var Asset $asset */
            $asset = $consumerAsset->asset;

            if (!$asset->isImage()) {
                abort(400, 'Asset ' . $consumerAsset->ca_key . ' is not an image.');
            }

            $targetSize = $this->getImageSize($asset);

            $image = $asset->getResizedImage($targetSize[0], $targetSize[1]);
            $data = $image->getData();

            // measure the image.
            $size = getimagesizefromstring($data);

            // is new row?
            if ($colCount % $columns === 0) {
                $height += $rowHeight;
                $width = max($width, $rowWidth);

                $rowWidth = 0;
                $rowHeight = 0;
            }
            $colCount ++;

            // update the dimensions of our target image
            $rowWidth += $size[0];
            $rowHeight = max($rowHeight, $size[1]);

            // Store the image in a temporary file (again)
            $tmpFile = tempnam(sys_get_temp_dir(), 'asset');
            $tmpFiles[] = $tmpFile;

            file_put_contents($tmpFile, $data);

            $images[] = [
                'size' => $size,
                'image' => Image::make($tmpFile)
            ];
        }

        $height += $rowHeight;
        $width = max($width, $rowWidth);

        // Now put both in one canvas.
        $canvas = Image::canvas($width, $height);

        /**
         * Start the actual drawing.
         */
        $rowHeight = 0;
        $colCount = 0;
        $x = 0;
        $y = 0;
        foreach ($images as $image) {

            if (($colCount > 0) && $colCount % $columns === 0) {
                $x = 0;
                $y += $rowHeight;
                $rowHeight = $image['size'][1];
            }

            $canvas->insert($image['image'], 'top-left', $x, $y);

            $rowHeight = max($image['size'][1], $rowHeight);
            $x += $image['size'][0];

            $colCount ++;
        }

        $ext = explode('/', $firstAsset->mimetype);
        if (count($ext) > 1) {
            $ext = $ext[1];
        }

        switch ($ext) {
            case 'png':
                $output = $canvas->encode('png');
                break;

            case 'jpg':
            case 'jpeg':
                $output =  $canvas->encode('jpeg');
                break;

            default:
                $output = $canvas->encode();
                break;
        }

        // Remove all temporary files we just created
        foreach ($tmpFiles as $tmpFile) {
            unlink($tmpFile);
        }

        return $output;
    }

    /**
     * @param Asset $asset
     * @return array
     * @throws \Exception
     */
    protected function getCacheHeaders(Asset $asset)
    {
        $expireInterval = new DateInterval('P1Y');
        $expireDate = (new DateTime())->add($expireInterval);

        return [
            'Expires' => $expireDate->format('r'),
            'Vary: Origin, Access-Control-Request-Headers, Access-Control-Request-Method',
            'Last-Modified' => $asset->created_at ? $asset->created_at->format('r') : null,
            'Cache-Control' => 'max-age=' . $this->dateIntervalToSeconds($expireInterval) . ', public',
            'Access-Control-Allow-Origin' => '*'
        ];
    }

    /**
     * @param Asset $asset
     * @param array $forceHeaders
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     * @throws \Exception
     */
    protected function getAssetResponse(Asset $asset, $forceHeaders = [])
    {
        $useRedirect = \Request::get('redirect', \Config::get('assets.s3.redirect'));

        // Look for redirect header
        $redirectHeader = \Request::header('X-Asset-Redirect');
        if ($redirectHeader) {
            $redirectHeader = trim($redirectHeader);
            $useRedirect =
                strtolower($redirectHeader) === 'true' ||
                $redirectHeader === 1;
        }

        if ($asset->disk === 's3' && $useRedirect) {
            $url = $this->getS3RedirectUrl($asset);
            return redirect($url, 301, $this->getCacheHeaders($asset));
        } else {
            return parent::getAssetResponse($asset, $forceHeaders);
        }
    }

    /**
     * @param Asset $asset
     * @return string
     */
    protected function getS3RedirectUrl(Asset $asset)
    {
        $cloudfront = \Config::get('assets.s3.cloudfront');
        if ($cloudfront) {
            return $cloudfront . '/' . $asset->path;
        } else {
            $region = \Config::get('filesystems.disks.s3.region');
            $bucket = \Config::get('filesystems.disks.s3.bucket');

            return 'https://s3.' . $region . '.amazonaws.com/' . $bucket . '/' . $asset->path;
        }
    }
}
