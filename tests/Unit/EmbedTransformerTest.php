<?php

namespace Tests\Unit;

use App\Lib\EmbedTransformer;
use Tests\TestCase;

class EmbedTransformerTest extends TestCase
{
    private EmbedTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new EmbedTransformer;
        config(['app.url' => 'https://keiforum.nl']);
    }

    public function test_internal_link_gets_wire_navigate_instead_of_target_blank(): void
    {
        $html = '<a href="https://keiforum.nl/topics/1" target="_blank">Topic</a>';
        $result = $this->transformer->transform($html);

        $this->assertStringContainsString('wire:navigate', $result);
        $this->assertStringNotContainsString('target="_blank"', $result);
    }

    public function test_internal_link_without_target_blank_gets_wire_navigate(): void
    {
        $html = '<a href="https://keiforum.nl/topics/1">Topic</a>';
        $result = $this->transformer->transform($html);

        $this->assertStringContainsString('wire:navigate', $result);
    }

    public function test_external_link_opens_in_new_window_with_ugc_rel(): void
    {
        $html = '<a href="https://example.com/page" rel="nofollow noopener" target="_blank">External</a>';
        $result = $this->transformer->transform($html);

        $this->assertSame('<a href="https://example.com/page" rel="nofollow ugc noopener" target="_blank">External</a>', $result);
    }

    public function test_external_link_without_target_gets_new_window(): void
    {
        $html = '<p><a href="https://www.nieuwsplein33.nl/nieuws/1">Lees het hele artikel</a></p>';
        $result = $this->transformer->transform($html);

        $this->assertSame('<p><a href="https://www.nieuwsplein33.nl/nieuws/1" rel="nofollow ugc noopener" target="_blank">Lees het hele artikel</a></p>', $result);
    }

    public function test_relative_internal_link_loses_target_and_nofollow(): void
    {
        $html = '<a href="/@keiforum" rel="nofollow noopener" target="_blank" data-mention="true">@keiforum</a>';
        $result = $this->transformer->transform($html);

        $this->assertSame('<a href="/@keiforum" data-mention="true" wire:navigate>@keiforum</a>', $result);
    }

    public function test_internal_link_loses_nofollow(): void
    {
        $html = '<a href="https://keiforum.nl/algemeen/1/test" rel="nofollow noopener" target="_blank">Topic</a>';
        $result = $this->transformer->transform($html);

        $this->assertSame('<a href="https://keiforum.nl/algemeen/1/test" wire:navigate>Topic</a>', $result);
    }

    public function test_lookalike_domain_is_external(): void
    {
        $html = '<a href="https://keiforum.nl.example.com/page">Lookalike</a>';
        $result = $this->transformer->transform($html);

        $this->assertStringNotContainsString('wire:navigate', $result);
        $this->assertStringContainsString('target="_blank"', $result);
    }

    public function test_protocol_relative_link_is_unchanged(): void
    {
        $html = '<a href="//example.com/page">Elsewhere</a>';

        $this->assertSame($html, $this->transformer->transform($html));
    }

    public function test_mailto_and_anchor_links_are_unchanged(): void
    {
        $html = '<a href="mailto:mail@keiforum.nl">Mail</a> <a href="#reacties">Reacties</a>';

        $this->assertSame($html, $this->transformer->transform($html));
    }

    public function test_embedded_image_link_opens_in_new_window(): void
    {
        $html = '<a href="https://example.com/photo.jpg">https://example.com/photo.jpg</a>';
        $result = $this->transformer->transform($html);

        $this->assertStringContainsString('<img alt="" loading="lazy" src="https://example.com/photo.jpg">', $result);
        $this->assertStringContainsString('rel="nofollow ugc noopener" target="_blank"', $result);
    }

    public function test_wire_navigate_not_added_twice_on_already_converted_link(): void
    {
        $html = '<a href="https://keiforum.nl/topics/1" wire:navigate>Topic</a>';
        $result = $this->transformer->transform($html);

        $this->assertEquals(1, substr_count($result, 'wire:navigate'));
    }
}
