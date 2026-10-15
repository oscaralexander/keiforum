<?php

namespace App\Lib;

use DOMCdataSection;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Reduces HTML from the editor to an allowlist of tags and attributes, so
 * it can safely be rendered unescaped.
 */
class HtmlSanitizer
{
    /**
     * Allowed tags and their allowed attributes.
     *
     * @var array<string, list<string>>
     */
    protected const ALLOWED_TAGS = [
        'a' => ['data-embed', 'data-mention', 'data-raw-url', 'href'],
        'b' => [],
        'blockquote' => [],
        'br' => [],
        'del' => [],
        'em' => [],
        'i' => [],
        'img' => ['alt', 'src'],
        'li' => [],
        'ol' => [],
        'p' => [],
        's' => [],
        'strong' => [],
        'ul' => [],
    ];

    /**
     * Tags that are removed together with their contents.
     *
     * @var list<string>
     */
    protected const REMOVED_TAGS = [
        'embed', 'form', 'iframe', 'math', 'noscript', 'object', 'script',
        'select', 'style', 'svg', 'template', 'textarea', 'title',
    ];

    public function sanitize(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><!DOCTYPE html><html><body>'.$html.'</body></html>');
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $dom->getElementsByTagName('body')->item(0);

        if (! $body) {
            return '';
        }

        $this->cleanChildren($body);

        $output = '';

        foreach ($body->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        return trim($output);
    }

    protected function cleanChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMText && ! $child instanceof DOMCdataSection) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                $node->removeChild($child);

                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::REMOVED_TAGS, true)) {
                $node->removeChild($child);

                continue;
            }

            $this->cleanChildren($child);

            if (! array_key_exists($tag, self::ALLOWED_TAGS)) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }

                $node->removeChild($child);

                continue;
            }

            $this->cleanAttributes($child, self::ALLOWED_TAGS[$tag]);
        }
    }

    /**
     * @param  list<string>  $allowedAttributes
     */
    protected function cleanAttributes(DOMElement $element, array $allowedAttributes): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (! in_array(strtolower($attribute->name), $allowedAttributes, true)) {
                $element->removeAttribute($attribute->name);
            }
        }

        foreach (['data-embed', 'data-mention', 'data-raw-url'] as $flag) {
            if ($element->hasAttribute($flag)) {
                $element->setAttribute($flag, 'true');
            }
        }

        if ($element->tagName === 'a') {
            if (! $this->isSafeUrl($element->getAttribute('href'), allowMailto: true)) {
                $element->removeAttribute('href');
            }

            $element->setAttribute('rel', 'nofollow noopener ugc');
            $element->setAttribute('target', '_blank');
        }

        if ($element->tagName === 'img' && ! $this->isSafeUrl($element->getAttribute('src'), requireHttps: true)) {
            $element->parentNode?->removeChild($element);
        }
    }

    protected function isSafeUrl(string $url, bool $allowMailto = false, bool $requireHttps = false): bool
    {
        $url = trim($url);

        if ($requireHttps) {
            return str_starts_with(strtolower($url), 'https://');
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        return (bool) preg_match($allowMailto ? '/^(https?:\/\/|mailto:)/i' : '/^https?:\/\//i', $url);
    }
}
