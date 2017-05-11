<?php

namespace App\Http\Controllers\Api;

use App\Models\Consumer;
use App\Models\ConsumerAsset;
use CatLab\Assets\Laravel\Helpers\AssetUploader;
use CatLab\Assets\Laravel\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Class UploadController
 * @package App\Http\Controllers
 */
class UploadController
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        // Figure out which consumer this is.
        /** @var Consumer $consumer */
        $consumer = $request->input('consumer');

        /** @var ConsumerAsset[] $consumerAssets */
        $consumerAssets = [];

        foreach ($request->files as $file) {

            /** @var UploadedFile $file */

            $asset = $this->uploadFile($file);

            $consumerAsset = ConsumerAsset::createFromAsset($asset, $consumer);
            $consumerAsset->name = $file->getClientOriginalName();

            $consumerAsset->save();

            $consumerAssets[] = $consumerAsset;
        }

        // Prepare output
        $out = [
            'success' => true,
            'assets' => []
        ];

        foreach ($consumerAssets as $v) {
            $out['assets'][] = $v->getData();
        }

        return \Response::json($out);
    }

    /**
     * Upload a file and return an asset.
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
     * @return Asset
     */
    protected function uploadFile(\Symfony\Component\HttpFoundation\File\UploadedFile $file)
    {
        $uploader = new AssetUploader();

        // Look for duplicate file
        $asset = $uploader->getDuplicate($file);
        if (!$asset) {
            $asset = $uploader->uploadFile($file);
        }

        return $asset;
    }

}