<?php

namespace App\Http\Controllers;

use App\Models\Combination;
use App\Models\ConsumerAsset;
use CatLab\Assets\Laravel\Helpers\AssetUploader;
use CatLab\Assets\Laravel\Models\Asset;
use Illuminate\Http\UploadedFile;
use DateInterval;
use DateTime;
use Image;
use Request;
use Response;

/**
 * Class AssetController
 * @package App\Http\Controllers
 */
class AssetController extends \CatLab\Assets\Laravel\Controllers\AssetController
{
    const SIZE_THUMBNAIL = 'thumbnail';
    const SIZE_ORIGINAL = 'original';
    const SIZE_LOWRES = 'lowres';
    const SIZE_RECTANGLE = 'rectangle';
    const SIZE_DIN = 'din';

    /**
     * View an asset
     * @param $key
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewConsumerAsset($key)
    {
        $consumerAsset = ConsumerAsset::assetKey($key)->first();
        if (!$consumerAsset) {
            abort(404, 'Asset not found.');
        }

        $asset = $consumerAsset->asset;
        return $this->viewAsset($asset);
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
     * @return mixed
     */
    public function combine()
    {
        $assetsKeys = Request::query('assets');
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

        $cols = Request::input('cols', 2);
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
        $combination->save();

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

            $images[] = [
                'size' => $size,
                'image' => Image::make(imagecreatefromstring($data))
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
                return $canvas->encode('png');

            case 'jpg':
            case 'jpeg':
                return $canvas->encode('jpeg');

            default:
                return $canvas->encode();
        }
    }

    /**
     * @param Asset $asset
     * @return array
     */
    protected function getCacheHeaders(Asset $asset)
    {
        $expireInterval = new DateInterval('P1Y');
        $expireDate = (new DateTime())->add($expireInterval);

        return [
            'Expires' => $expireDate->format('r'),
            'Last-Modified' => $asset->created_at ? $asset->created_at->format('r') : null,
            'Cache-Control' => 'max-age=' . $this->dateIntervalToSeconds($expireInterval) . ', public',
            'Access-Control-Allow-Origin' => '*'
        ];
    }

    /**
     * @param Asset $asset
     * @param string[] $forceHeaders
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    protected function getAssetResponse(Asset $asset, $forceHeaders = [])
    {
        $useRedirect = \Request::get('redirect');
        if ($asset->disk === 's3' && $useRedirect) {
            $region = \Config::get('filesystems.disks.s3.region');
            $bucket = \Config::get('filesystems.disks.s3.bucket');

            $url = 'https://s3.' . $region . '.amazonaws.com/' . $bucket . '/' . $asset->path;
            return Response::redirectTo($url, 301);
        } else {
            return parent::getAssetResponse($asset, $forceHeaders);
        }
    }
}