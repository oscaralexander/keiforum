<?php

namespace App\Lib;

class EmbedTransformer
{
    public function transform(string $html): string
    {
        // YouTube
        $html = preg_replace_callback(
            '/<a[^>]*href=["\'](https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)(?:[^"\']*)?)["\'][^>]*>(.*?)<\/a>/is',
            function ($matches) {
                $videoId = $matches[2];

                return sprintf(
                    '<div class="body__video"><iframe width="560" height="315" src="https://www.youtube.com/embed/%s" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>',
                    htmlspecialchars($videoId, ENT_QUOTES, 'UTF-8')
                );
            },
            $html
        );

        // Image links to embedded images
        $html = preg_replace_callback(
            '/<a[^>]*href=["\'](https?:\/\/[^"\']+\.(?:jpe?g|png|webp|avif|gif))["\'][^>]*>(.*?)<\/a>/is',
            function ($matches) {
                $src = $matches[1];

                return sprintf(
                    '<a href="%s" target="_blank"><img alt="" loading="lazy" src="%s"></a>',
                    htmlspecialchars($src, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($src, ENT_QUOTES, 'UTF-8')
                );
            },
            $html
        );

        return $html;
    }


}
