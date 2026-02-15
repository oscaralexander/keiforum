<?php

namespace App\Http\Controllers;

use App\Lib\Image;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ImageProxyController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $data = $request->validate([
            'h' => ['nullable', 'integer', 'min:1', 'max:4096'],
            'q' => ['nullable', 'integer', 'min:10', 'max:100'],
            'src' => ['required', 'string', 'max:2048'],
            'w' => ['nullable', 'integer', 'min:1', 'max:4096'],
        ]);

        return Image::serve($data['src'], $data['w'], $data['h'], $data['q']);
    }
}