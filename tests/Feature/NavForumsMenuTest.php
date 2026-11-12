<?php

namespace Tests\Feature;

use App\Models\Forum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavForumsMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_forums_link_has_a_dropdown_with_the_listed_forums(): void
    {
        $forum = Forum::factory()->create(['name' => 'Testforum']);
        $hidden = Forum::factory()->create(['is_hidden' => true, 'name' => 'Verborgen forum']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder(['nav__menu-item--dropdown', 'nav__dropdown nav__dropdown--left', route('forum.show', $forum)], false)
            ->assertDontSee(route('forum.show', $hidden), false);
    }

    public function test_mobile_menu_lists_the_forums_below_forums(): void
    {
        $forum = Forum::factory()->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder(['mobileMenu__submenu', route('forum.show', $forum)], false);
    }

    public function test_current_forum_is_active_in_the_mobile_menu(): void
    {
        $forum = Forum::factory()->create();

        $html = $this->get(route('forum.show', $forum))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/mobileMenu__submenu-link mobileMenu__submenu-link--active"\s+href="'.preg_quote(route('forum.show', $forum), '/').'"/',
            $html
        );
    }

    public function test_user_menu_is_opened_with_a_class_so_it_can_animate(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSee('id="navUserMenu"', false)
            ->assertSee("x-bind:class=\"{ 'is-open': isMenuOpen }\"", false);
    }
}
