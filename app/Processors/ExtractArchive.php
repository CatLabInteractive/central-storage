<?php


namespace App\Processors;

use App\Models\Asset;
use App\Models\ConsumerAsset;
use App\Models\Processor;
use App\Models\ProcessorJob;
use App\Models\Variation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;

/**
 * Class ExtractArchive
 * @package App\Processors
 */
class ExtractArchive extends Processor
{
    /**
     * @param ProcessorJob $job
     * @param $outputPath
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \Exception
     */
    protected function handle(ProcessorJob $job, $outputPath)
    {
        // move file to a temporary directory
        $tempFile = tempnam(sys_get_temp_dir(), 'extract-');

        /** @var Asset $asset */
        $asset = $job->consumerAsset->asset;
        $asset->saveToFile($tempFile);

        $fileCounter = 0;

        $zip = \Zipper::make($tempFile);
        foreach ($zip->listFiles() as $file) {
            $content = $zip->getFileContent($file);
            $variationName = $this->variation_name . ':' . (++ $fileCounter);

            // do we already have this variation?
            if ($asset->getVariation($variationName)) {
                continue;
            }

            $tmpFile = tempnam(sys_get_temp_dir(), 'extract-');
            file_put_contents($tmpFile, $content);

            $fileInfo = new UploadedFile($tmpFile, basename($file));
            $variation = $this->uploadProcessedFile(
                $job->consumerAsset,
                $fileInfo,
                $variationName,
                false,
                $job
            );

            $variation->variation_path = $file;
            $variation->save();

            unlink($tmpFile);
        }

        unlink($tempFile);
    }

    /**
     * Get the name of the desired variation (based on request parameters)
     * @param Asset $asset
     * @param \Illuminate\Http\Request $request
     * @param string $subPath
     * @return Variation|bool|null|\Symfony\Component\HttpFoundation\Response
     */
    public function getDesiredVariation(Asset $asset, \Illuminate\Http\Request $request, $subPath)
    {
        // no subpath? let's return an index.
        if (!$subPath && $this->getConfig('exposeFileIndex')) {
            $variations = $asset->variations()->where('processor_id', '=', $this->id)->get();
            $files = $variations->pluck('variation_path');

            return new JsonResponse($files);
        } elseif (!$subPath) {
            return new JsonResponse([ 'error' => [ 'message' => 'File not found.' ]], 404);
        }

        $variation = $asset->variations()->where('variation_path', '=', $subPath)->first();
        if (!$variation) {
            // we should return FALSE to notify the controller that we are still processing this variation.
            return false;
        }
        return $variation;
    }

    /**
     * @return array
     */
    public function getConfigValidation()
    {
        return [
            'exposeFileIndex' => 'required|boolean'
        ];
    }
}