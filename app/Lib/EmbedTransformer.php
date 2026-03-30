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

                // If the matched <a> tag does not have 'data-embed' attribute, return original match
                if (stripos($matches[0], 'data-embed') === false) {
                    return $matches[0];
                }

                return sprintf(
                    '<div class="formatted__video"><iframe width="560" height="315" src="https://www.youtube.com/embed/%s" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>',
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

        // Internal links: replace target="_blank" with wire:navigate
        $appUrl = rtrim(config('app.url'), '/');
        $html = preg_replace_callback(
            '/<a([^>]*)>/i',
            function ($matches) use ($appUrl) {
                $attrs = $matches[1];

                if (!preg_match('/href=["\']([^"\']*)["\']/', $attrs, $hrefMatch)) {
                    return $matches[0];
                }

                $href = $hrefMatch[1];

                if (!str_starts_with($href, $appUrl)) {
                    return $matches[0];
                }

                $attrs = preg_replace('/\s*target=["\'][^"\']*["\']/', '', $attrs);
                $attrs = preg_replace('/\s*wire:navigate/', '', $attrs);

                return '<a' . $attrs . ' wire:navigate>';
            },
            $html
        );

        return $html;
    }
}
