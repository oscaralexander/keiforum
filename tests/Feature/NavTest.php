<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_menu_links_to_settings_between_profile_and_logout(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder([
                route('profile'),
                route('settings'),
                route('logout'),
            ], false);
    }

    public function test_guests_do_not_see_settings_link(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee(route('settings'), false);
    }
}
