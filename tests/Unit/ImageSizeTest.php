<?php

namespace Tests\Unit;

use App\Http\Controllers\AssetController;
use CatLab\Assets\Laravel\Models\Asset;
use Illuminate\Http\Request;
use Tests\TestCase;

class ImageSizeTest extends TestCase
{
    private function imageSize(array $query, $width, $height)
    {
        $this->app->instance('request', Request::create('/assets/abc', 'GET', $query));

        $asset = new Asset();
        $asset->width = $width;
        $asset->height = $height;

        $method = new \ReflectionMethod(AssetController::class, 'getImageSize');
        return $method->invoke(new AssetController(), $asset);
    }

    public function testLowresScalesALargeImageDownTo1000High()
    {
        $this->assertEquals([ 2461, 1000 ], $this->imageSize([ 'size' => 'lowres' ], 6408, 2604));
    }

    /**
     * Scaling up only made the file bigger (a 798 x 601 PNG came back as
     * 1328 x 1000, 276 KB to 992 KB), the same as maxWidth and maxHeight.
     */
    public function testLowresKeepsASmallImageAtItsOwnSize()
    {
        $this->assertEquals([ 798, 601 ], $this->imageSize([ 'size' => 'lowres' ], 798, 601));
        $this->assertEquals([ 1200, 1000 ], $this->imageSize([ 'size' => 'lowres' ], 1200, 1000));
    }

    public function testThumbnailKeepsATinyImageAtItsOwnSize()
    {
        $this->assertEquals([ 200, 100 ], $this->imageSize([ 'size' => 'thumbnail' ], 800, 400));
        $this->assertEquals([ 64, 48 ], $this->imageSize([ 'size' => 'thumbnail' ], 64, 48));
    }
}
