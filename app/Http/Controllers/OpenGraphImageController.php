<?php

namespace App\Http\Controllers;

use App\Lib\OpenGraphImage;
use App\Models\Topic;
use Illuminate\Http\Response;

class OpenGraphImageController extends Controller
{
    /**
     * The URL changes whenever the image does, so it can be cached for long.
     */
    private const CACHE_TTL = 60 * 60 * 24 * 30;

    public function __invoke(Topic $topic, OpenGraphImage $openGraphImage): Response
    {
        return response($openGraphImage->jpeg($topic), Response::HTTP_OK, [
            'Cache-Control' => 'public, max-age='.self::CACHE_TTL,
            'Content-Type' => 'image/jpeg',
        ]);
    }
}
