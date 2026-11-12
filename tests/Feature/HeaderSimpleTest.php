<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderSimpleTest extends TestCase
{
    use RefreshDatabase;

    public function test_simple_header_renders_title_and_intro(): void
    {
        $html = (string) $this->blade('<x-header-simple title="Inloggen" intro="Welkom <b>terug</b>" />');

        $this->assertStringContainsString('<header class="headerSimple">', $html);
        $this->assertStringContainsString('<h1>Inloggen</h1>', $html);
        $this->assertStringContainsString('<p class="headerSimple__intro">Welkom <b>terug</b></p>', $html);
    }

    public function test_simple_header_without_intro(): void
    {
        $html = (string) $this->blade('<x-header-simple title="Instellingen" />');

        $this->assertStringNotContainsString('headerSimple__intro', $html);
    }

    public function test_simple_layout_pages_use_the_simple_header(): void
    {
        foreach (['login', 'register', 'forgot-password'] as $route) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee('<header class="headerSimple">', false)
                ->assertDontSee('class="contrast header', false);
        }
    }
}
