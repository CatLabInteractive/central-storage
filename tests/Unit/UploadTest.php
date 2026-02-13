<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\UploadController;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UploadTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {

    }

    /**
     * Test that isImage correctly identifies image MIME types.
     */
    public function testIsImageReturnsTrueForImageFiles()
    {
        $controller = new class extends UploadController {
            public function testIsImage(UploadedFile $file): bool
            {
                return $this->isImage($file);
            }
        };

        $imageFile = UploadedFile::fake()->image('photo.jpg');
        $this->assertTrue($controller->testIsImage($imageFile));
    }

    /**
     * Test that isImage returns false for non-image MIME types.
     */
    public function testIsImageReturnsFalseForNonImageFiles()
    {
        $controller = new class extends UploadController {
            public function testIsImage(UploadedFile $file): bool
            {
                return $this->isImage($file);
            }
        };

        $videoFile = UploadedFile::fake()->create('video.mp4', 100, 'video/mp4');
        $this->assertFalse($controller->testIsImage($videoFile));

        $textFile = UploadedFile::fake()->create('document.txt', 100, 'text/plain');
        $this->assertFalse($controller->testIsImage($textFile));
    }

    /**
     * Test that the max image file size config is set correctly.
     */
    public function testMaxImageFileSizeConfigExists()
    {
        $maxSize = config('assets.max_image_file_size');
        $this->assertNotNull($maxSize);
        $this->assertEquals(20 * 1024 * 1024, $maxSize);
    }
}
