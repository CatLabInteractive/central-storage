<?php

namespace App\Http\Controllers;

use App\Models\ConsumerAsset;
use CatLab\Assets\Laravel\Models\Asset;
use Request;

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
}