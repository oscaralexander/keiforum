<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserColumnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_admin_defaults_to_false(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_is_admin_can_be_set_to_true(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($user->is_admin);
    }

    public function test_banned_until_defaults_to_null(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->banned_until);
    }

    public function test_banned_until_can_be_set(): void
    {
        $user = User::factory()->banned()->create();

        $this->assertNotNull($user->banned_until);
        $this->assertTrue($user->banned_until->isFuture());
    }
}
