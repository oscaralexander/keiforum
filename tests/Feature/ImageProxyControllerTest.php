<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageProxyControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->clearImageProxyCache();
    }

    protected function tearDown(): void
    {
        $this->clearImageProxyCache();

        parent::tearDown();
    }

    public function test_it_serves_local_images_as_webp(): void
    {
        $png = $this->fakePng();

        Storage::disk('public')->put('test.png', $png);

        $response = $this->get('/image-proxy?src=test.png&w=20');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/webp');
        $this->assertNotEmpty($response->getContent());

        Storage::disk('public')->delete('test.png');
    }

    public function test_it_can_proxy_remote_images(): void
    {
        $remoteUrl = 'https://example.com/image.png';
        $png = $this->fakePng();

        Http::fake([
            $remoteUrl => Http::response($png, 200, ['Content-Type' => 'image/png']),
        ]);

        $response = $this->get('/image-proxy?src='.urlencode($remoteUrl).'&h=10');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/webp');
    }

    public function test_it_reuses_cached_images(): void
    {
        $remoteUrl = 'https://example.com/cache-image.png';
        $png = $this->fakePng();

        Http::fake([
            $remoteUrl => Http::response($png, 200, ['Content-Type' => 'image/png']),
        ]);

        $this->get('/image-proxy?src='.urlencode($remoteUrl).'&w=25&h=30')
            ->assertOk();

        Http::assertSentCount(1);

        Http::fake(function () {
            $this->fail('Remote image should not be fetched when cache exists.');
        });

        $this->get('/image-proxy?src='.urlencode($remoteUrl).'&w=25&h=30')
            ->assertOk();

        Http::assertSentCount(0);
    }

    public function test_cover_scaling_respects_target_dimensions(): void
    {
        if (! function_exists('imagecreatefromwebp')) {
            $this->markTestSkipped('GD WebP decoding support is required for this assertion.');
        }

        $remoteUrl = 'https://example.com/cover-image.png';
        $png = $this->fakePng();

        Http::fake([
            $remoteUrl => Http::response($png, 200, ['Content-Type' => 'image/png']),
        ]);

        $response = $this->get('/image-proxy?src='.urlencode($remoteUrl).'&w=37&h=19')
            ->assertOk();

        $image = $this->decodeWebp($response->getContent());

        $this->assertSame(37, imagesx($image));
        $this->assertSame(19, imagesy($image));

        imagedestroy($image);
    }

    private function fakePng(): string
    {
        $image = imagecreatetruecolor(10, 10);
        $background = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, 9, 9, $background);

        ob_start();
        imagepng($image);
        imagedestroy($image);

        return (string) ob_get_clean();
    }

    private function clearImageProxyCache(): void
    {
        $path = storage_path('app/image-proxy');

        if (File::isDirectory($path)) {
            File::deleteDirectory($path);
        }
    }

    private function decodeWebp(string $contents)
    {
        $temporary = tempnam(sys_get_temp_dir(), 'webp');

        file_put_contents($temporary, $contents);

        try {
            return imagecreatefromwebp($temporary);
        } finally {
            @unlink($temporary);
        }
    }
}

