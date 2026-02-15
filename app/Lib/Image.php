<?php

namespace App\Lib;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;
use Throwable;

class Image
{
    public const CACHE_ROOT = 'img-cache';

    public const CACHE_TTL = 60 * 60 * 24 * 14; // 14 days

    public const MAX_FILE_SIZE = 6_144_000;

    protected ImageInterface $image;

    protected ImageManager $images;

    protected string $contents;

    protected ?string $height = null;

    protected ?string $width = null;

    protected int $quality = 100;

    public function __construct()
    {
        $this->images = extension_loaded('imagick')
            ? new ImageManager(new ImagickDriver())
            : new ImageManager(new GdDriver());
    }

    protected static function dimensionsFolder(?int $width, ?int $height): string
    {      
        return ($width ?? 'auto') . 'x' . ($height ?? 'auto');
    }

    public function encode(int $quality = 100): string
    {
        return $this->image->toWebp(quality: $quality, strip: true)->toString();
    }

    private function fetchImageLocal(string $path): string
    {
        if (!is_readable($path)) {
            $storagePath = storage_path('app/public') . DIRECTORY_SEPARATOR . $path;

            if (is_readable($storagePath)) {
                $path = $storagePath;
            } else {
                abort(Response::HTTP_NOT_FOUND, __('image.error.image_not_found'));
            }
        }

        $contents = file_get_contents($path);
        return $contents ?: '';
    }

    private function fetchImageRemote(string $url): string
    {
        $response = Http::timeout(5)
            ->accept('image/*')
            ->get($url);

        if (!$response->successful()) {
            abort(Response::HTTP_BAD_GATEWAY, __('image.error.bad_gateway'));
        }

        return (string) $response->body();
    }

    public function read(string $src): static
    {
        $contents = filter_var($src, FILTER_VALIDATE_URL)
            ? $this->fetchImageRemote($src)
            : $this->fetchImageLocal($src);

        if (empty($contents)) {
            abort(Response::HTTP_NOT_FOUND, __('image.error.image_not_found'));
        }

        try {
            $this->image = $this->images->read($contents);
        } catch (Throwable) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, __('image.error.unprocessable_entity'));
        }

        return $this;
    }

    protected static function readCache(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        if (filemtime($path) < (time() - self::CACHE_TTL)) {
            return null;
        }

        return Storage::disk('public')->get($path);
    }

    public function resize(?int $width = null, ?int $height = null): static
    {
        if ($width === null && $height === null) {
            return $this;
        }

        if ($width !== null && $height !== null) {
            $this->image = $this->image->cover($width, $height);
        }

        $this->image = $this->image->scaleDown($width, $height);
        return $this;
    }

    public static function cacheFilePath(string $src, ?int $width, ?int $height): string
    {
        return self::CACHE_ROOT . DIRECTORY_SEPARATOR . static::dimensionsFolder($width, $height) . DIRECTORY_SEPARATOR . sha1($src) . '.webp';
    }

    public static function serve(string $src, ?int $width, ?int $height, int $quality): Response
    {
        $cachePath = static::cacheFilePath($src, $width, $height);
        $contents = static::readCache($cachePath);

        if (!$contents) {
            $image = new self();
            $contents = $image
                ->read($src)
                ->resize($width, $height)
                ->encode($quality);
        }

        Storage::disk('public')->put($cachePath, $contents);

        return response($contents, Response::HTTP_OK, [
            'Cache-Control' => 'public, max-age=' . self::CACHE_TTL,
            'Content-Type' => 'image/webp',
        ]);
    }
}