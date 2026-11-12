<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderCtaTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_with_call_to_action_uses_two_column_layout(): void
    {
        $html = (string) $this->blade(
            '<x-header title="Titel"><x-slot:cta><x-btn href="/nieuw" large primary>Nieuw</x-btn></x-slot:cta></x-header>'
        );

        $this->assertStringContainsString('header--cta', $html);
        $this->assertStringContainsString('<div class="header__cta">', $html);
        $this->assertStringContainsString('btn--large', $html);
    }

    public function test_header_without_call_to_action_keeps_default_layout(): void
    {
        $html = (string) $this->blade('<x-header title="Titel" />');

        $this->assertStringNotContainsString('header--cta', $html);
        $this->assertStringNotContainsString('header__cta', $html);
    }

    public function test_home_page_shows_new_topic_button_as_call_to_action(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('header--cta', false)
            ->assertSee('btn--large', false);
    }

    public function test_header_always_uses_contrast_theme(): void
    {
        $html = (string) $this->blade('<x-header title="Titel" />');

        $this->assertMatchesRegularExpression('/<header\s+class="contrast header"/', $html);
    }

    public function test_path_is_outside_the_header_text(): void
    {
        $html = (string) $this->blade('<x-header title="Titel" />');

        $this->assertMatchesRegularExpression('#<div class="header__path">\s*<ul class="path">.*</ul>\s*</div>\s*<div class="header__text">#s', $html);
    }

    public function test_hidden_path_is_not_rendered(): void
    {
        $html = (string) $this->blade('<x-header hide-path title="Titel" />');

        $this->assertStringNotContainsString('header__path', $html);
    }
}
