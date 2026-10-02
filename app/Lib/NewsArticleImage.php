<?php

namespace App\Lib;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NewsArticleImage
{
    /**
     * Fetch the lead image of a Nieuwsplein33 article, along with its caption
     * and copyright credit as shown in the article's <figcaption>.
     *
     * @return array{url: string, caption: ?string, credit: ?string}|null
     */
    public function fetch(string $articleUrl, ?string $feedImageUrl = null): ?array
    {
        $response = Http::timeout(10)->get($articleUrl);

        if (! $response->successful()) {
            return null;
        }

        return $this->parse($response->body(), $feedImageUrl);
    }

    /**
     * Prefer the image that matches the feed's enclosure; fall back to the
     * first image in the article.
     *
     * @return array{url: string, caption: ?string, credit: ?string}|null
     */
    public function parse(string $html, ?string $feedImageUrl = null): ?array
    {
        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $components = $xpath->query("//*[@__component='api.api-image'][.//img[@src]]");

        if (! $components || $components->length === 0) {
            return null;
        }

        $feedImagePath = $feedImageUrl ? parse_url($feedImageUrl, PHP_URL_PATH) : null;
        $component = $components->item(0);

        foreach ($components as $candidate) {
            $src = $this->imageSource($xpath, $candidate);

            if ($feedImagePath && parse_url($src, PHP_URL_PATH) === $feedImagePath) {
                $component = $candidate;
                break;
            }
        }

        return [
            'url' => $this->imageSource($xpath, $component),
            'caption' => $this->text($xpath, $component, 'description'),
            'credit' => $this->text($xpath, $component, 'copyright'),
        ];
    }

    private function imageSource(DOMXPath $xpath, DOMElement $component): string
    {
        return $xpath->query('.//img[@src]', $component)->item(0)->getAttribute('src');
    }

    private function text(DOMXPath $xpath, DOMElement $component, string $class): ?string
    {
        $node = $xpath->query(".//figcaption//*[contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')]", $component)->item(0);
        $text = $node ? Str::squish($node->textContent) : '';

        return $text !== '' ? Str::limit($text, 250) : null;
    }
}
