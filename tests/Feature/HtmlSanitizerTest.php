<?php

namespace Tests\Feature;

use App\Lib\HtmlSanitizer;
use Tests\TestCase;

class HtmlSanitizerTest extends TestCase
{
    private function sanitize(string $html): string
    {
        return (new HtmlSanitizer)->sanitize($html);
    }

    public function test_allowed_formatting_is_kept(): void
    {
        $html = '<p><strong>Vet</strong>, <em>schuin</em> en <s>doorgestreept</s></p><ul><li>Een</li></ul><blockquote><p>Citaat</p></blockquote>';

        $this->assertSame($html, $this->sanitize($html));
    }

    public function test_scripts_and_styles_are_removed_with_their_contents(): void
    {
        $this->assertSame('<p>Hallo</p>', $this->sanitize('<p>Hallo</p><script>alert(1)</script><style>p{}</style>'));
    }

    public function test_event_handlers_and_unknown_attributes_are_removed(): void
    {
        $this->assertSame('<p>Hallo</p>', $this->sanitize('<p onclick="alert(1)" class="x" style="color:red">Hallo</p>'));
    }

    public function test_unknown_tags_are_unwrapped(): void
    {
        $this->assertSame('<p>Hallo <strong>daar</strong></p>', $this->sanitize('<div><p><span>Hallo</span> <strong>daar</strong></p></div>'));
    }

    public function test_links_get_safe_attributes_and_javascript_urls_are_dropped(): void
    {
        $this->assertSame(
            '<p><a href="https://example.com" rel="nofollow noopener ugc" target="_blank">ok</a> <a rel="nofollow noopener ugc" target="_blank">nee</a></p>',
            $this->sanitize('<p><a href="https://example.com" target="_self">ok</a> <a href="javascript:alert(1)">nee</a></p>')
        );
    }

    public function test_images_need_an_https_source(): void
    {
        $this->assertSame(
            '<p><img alt="Poster" src="https://i.ibb.co/x.jpg"></p>',
            $this->sanitize('<p><img alt="Poster" src="https://i.ibb.co/x.jpg" onerror="alert(1)"><img src="data:image/png;base64,AAAA"><img src="javascript:alert(1)"></p>')
        );
    }

    public function test_comments_are_removed(): void
    {
        $this->assertSame('<p>Hallo</p>', $this->sanitize('<p>Hallo<!-- verborgen --></p>'));
    }

    public function test_empty_input_returns_empty_string(): void
    {
        $this->assertSame('', $this->sanitize('   '));
    }

    public function test_text_is_kept_escaped(): void
    {
        $this->assertSame('<p>1 &lt; 2 &amp; &lt;b&gt;</p>', $this->sanitize('<p>1 &lt; 2 &amp; &lt;b&gt;</p>'));
    }

    public function test_editor_link_flags_are_kept_and_normalized(): void
    {
        $this->assertSame(
            '<p><a href="https://keiforum.nl/@jan" data-mention="true" rel="nofollow noopener ugc" target="_blank">@jan</a></p>',
            $this->sanitize('<p><a href="https://keiforum.nl/@jan" data-mention="&quot;><script>" data-other="x">@jan</a></p>')
        );
    }
}
