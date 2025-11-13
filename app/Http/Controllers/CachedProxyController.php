<?php

namespace App\Http\Controllers;

use App\Models\Consumer;
use App\Models\CachedProxyFile;
use CatLab\Assets\Laravel\Helpers\AssetUploader;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class PublicCachingController
 * @package App\Http\Controllers
 */
class CachedProxyController extends AssetController
{
    /**
     * @param Request $request
     * @param string $consumerKey
     * @param string $urlBase64
     * @param string $signature
     * @return \Illuminate\Http\JsonResponse|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cache(Request $request, $consumerKey, $urlBase64, $signature)
    {
        $consumer = Consumer::findFromKey($consumerKey);
        if (!$consumer) {
            abort(404, 'Consumer key invalid');
            return null;
        }


        // Check if url is valid
        $url = base64_decode($urlBase64);
        if (
            !$url ||
            !Str::startsWith(strtolower($url), [ 'http://', 'https://' ])
        ) {
            abort(404, 'Invalid url provided');
            return null;
        }

        // Check url signature
        $signedParameters = [
            'url' => $url
        ];

        if (!\CentralStorage::isValidParameters($signedParameters, $signature, $consumer->secret)) {
            abort(404, 'Invalid signature provided');
            return null;
        }

        // Look for an existing PublicCaching
        $publicCaching = CachedProxyFile::getFromUrl($consumer, $url);
        if ($publicCaching) {
            return $this->viewConsumerWithAsset($request, $publicCaching->asset, $consumer);
        }

        // None found? Create one.
        $tmpFilename = tempnam(sys_get_temp_dir(), 'centralstore_caching');

        // Download the file
        $client = new Client();

        try {
            $response = $client->request('GET', $url, ['sink' => $tmpFilename]);
        } catch (TransferException $e) {
            return redirect($url);
        }

        $parts = explode('/', $url);
        $originalName = $parts[count($parts) - 1];

        $contentType = $response->getHeader('content-type');

        $mimetype = '';
        if (count($contentType) > 0) {
            $mimetype = $contentType[0];
        }

        $size = filesize($tmpFilename);

        $uploadedFile = new UploadedFile(
            $tmpFilename,
            $originalName,
            $mimetype,
            $size
        );

        $uploader = new AssetUploader();

        // Look for duplicate file
        $asset = $uploader->getDuplicate($uploadedFile);
        if (!$asset) {
            $asset = $uploader->uploadFile($uploadedFile);
        }
        unlink($tmpFilename);

        // Store the
        $cachedProxyFile = CachedProxyFile::createFromAsset($asset, $consumer);
        $cachedProxyFile->public_url = $url;

        $cachedProxyFile->save();

        return $this->viewConsumerWithAsset($request, $cachedProxyFile->asset, $consumer);
    }
}
