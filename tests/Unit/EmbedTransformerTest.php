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

    public function test_external_link_is_unchanged(): void
    {
        $html = '<a href="https://example.com/page" target="_blank">External</a>';
        $result = $this->transformer->transform($html);

        $this->assertStringNotContainsString('wire:navigate', $result);
        $this->assertStringContainsString('target="_blank"', $result);
    }

    public function test_wire_navigate_not_added_twice_on_already_converted_link(): void
    {
        $html = '<a href="https://keiforum.nl/topics/1" wire:navigate>Topic</a>';
        $result = $this->transformer->transform($html);

        $this->assertEquals(1, substr_count($result, 'wire:navigate'));
    }
}
