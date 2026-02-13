<?php

namespace App\Http\Controllers\Api;

use App\Models\Consumer;
use App\Models\ConsumerAsset;
use App\Models\Processor;
use Carbon\Carbon;
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
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
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
            if (!$file->isValid()) {
                abort(400, 'Uploaded file is not valid: ' . $file->getErrorMessage());
            }

            // Check file size limit for images
            $maxImageFileSize = config('assets.max_image_file_size');
            if ($maxImageFileSize && $this->isImage($file) && $file->getSize() > $maxImageFileSize) {
                $maxInMB = round($maxImageFileSize / 1024 / 1024, 1);
                abort(422, 'Image file size exceeds the maximum allowed size of ' . $maxInMB . 'MB.');
            }

            $asset = $this->uploadFile($file);

            $consumerAsset = ConsumerAsset::createFromAsset($asset, $consumer);
            $consumerAsset->name = $file->getClientOriginalName();

            if ($request->post('expires')) {
                $expires = Carbon::parse($request->post('expires'));
                if ($expires) {
                    $consumerAsset->expires_at = $expires;
                }
            }

            $consumerAsset->save();

            // Processors
            foreach ($consumer->processors as $processor) {
                /** @var Processor $processor */
                if ($processor->isTriggered($asset)) {
                    $processor->process($consumerAsset);
                }
            }

            // Add to output list.
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
     * Remove a file
     * @param Request $request
     * @param $key
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(Request $request, $key)
    {
        /** @var Consumer $consumer */
        $consumer = $request->input('consumer');

        /** @var ConsumerAsset $asset */
        $asset = $consumer->consumerAssets()->assetKey($key)->first();
        if (!$asset) {
            return $this->error('Asset not found', 404);
        }

        $asset->delete();

        return \Response::json(
            [
                'success' => true
            ]
        );
    }

    /**
     * @param $message
     * @param $status
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error($message, $status)
    {
        return \Response::json([
            'error' => [
                'message' => $message
            ]
        ])->setStatusCode($status);
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

    /**
     * Check if the uploaded file is an image based on its MIME type.
     * @param UploadedFile $file
     * @return bool
     */
    protected function isImage(\Symfony\Component\HttpFoundation\File\UploadedFile $file)
    {
        $mimeType = $file->getMimeType();
        return str_starts_with($mimeType, 'image/');
    }

}
