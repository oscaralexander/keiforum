<?php

namespace App\Http\Controllers;

use App\Lib\Image;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ImageProxyController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $data = $request->validate([
            'f' => ['nullable', Rule::in(array_keys(Image::FORMATS))],
            'h' => ['nullable', 'integer', 'min:1', 'max:4096'],
            'q' => ['nullable', 'integer', 'min:10', 'max:100'],
            'src' => ['required', 'string', 'max:2048'],
            'w' => ['nullable', 'integer', 'min:1', 'max:4096'],
        ]);

        return Image::serve($data['src'], $data['w'] ?? null, $data['h'] ?? null, $data['q'] ?? 80, $data['f'] ?? 'webp');
    }
}
