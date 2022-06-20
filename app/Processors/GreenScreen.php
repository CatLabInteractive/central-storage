<?php


namespace App\Processors;

use App\Models\Asset;
use App\Models\ConsumerAsset;
use App\Models\Processor;
use App\Models\ProcessorJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

use Image;

/**
 *
 */
class GreenScreen extends Processor
{
    /**
     * @return array
     */
    public function getConfigValidation()
    {
        return [
            'background' => 'file',
            'threshold' => 'nullable|int|min:0|max:100',
            'area' => 'nullable|int|min:0',
            'saturation' => 'nullable|int|min:0|max:100',
            'backgroundColor' => 'nullable|string',
            'antialias' => 'nullable|int|min:0'

        ];
    }

    public function handle(ProcessorJob $job, $outputPath)
    {
        // move the asset to this directory
        $background = $this->getBackground();
        if (!$background) {
            return new JsonResponse('Cannot process greenscreen, background not set.', 400);
        }

        /** @var ConsumerAsset $consumerAsset */
        $consumerAsset = $job->consumerAsset;

        /** @var Asset $asset */
        $asset = $consumerAsset->asset;

        // make a temporary directory
        $tmpDir = tempnam(sys_get_temp_dir(),'greenscreen-');

        if (file_exists($tmpDir)) { unlink($tmpDir); }
        mkdir($tmpDir);

        $backgroundFileName = 'background.' . $background->getExtension();
        $assetFileName = 'foreground.' . $asset->getExtension();

        $background->saveToFile($tmpDir . '/' . $backgroundFileName);

        // Resize the asset to match the background
        $image = Image::make($asset->getData());
        $image = $image->fit($background->width, $background->height);

        file_put_contents($tmpDir . '/' . $assetFileName, $image->encode());

        //$asset->saveToFile($tmpDir . '/' . $assetFileName);

        chdir($tmpDir);
        $command = __DIR__ . '/scripts/greenscreen ' . $assetFileName . ' ' . $backgroundFileName . ' output.jpg';

        $output = shell_exec($command);

        $fileInfo = new UploadedFile($tmpDir . '/output.png', $job->consumerAsset->name . '-' . $asset->getExtension());
        $variation = $this->uploadProcessedFile(
            $job->consumerAsset,
            $fileInfo,
            $this->variation_name,
            false,
            $job
        );

        $variation->save();

        \File::deleteDirectory($tmpDir);
        $job->setState(ProcessorJob::STATE_FINISHED);
    }

    /**
     * @return Asset|null
     */
    protected function getBackground()
    {
        /** @var ConsumerAsset|null $consumerAsset */
        $consumerAsset = $this->getConfig('background');
        if (!$consumerAsset) {
            return null;
        }

        return $consumerAsset->asset;
    }

    /**
     * @param Processor $processor
     * @return bool
     */
    protected function isConfigSimilar(Processor $processor)
    {
        return false;
    }
}
