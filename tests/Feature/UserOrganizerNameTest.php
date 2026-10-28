<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class UserOrganizerNameTest extends TestCase
{
    public function test_organizer_name_prefers_the_name(): void
    {
        $user = User::factory()->make(['name' => 'Podium FLUOR', 'username' => 'podiumfluor']);

        $this->assertSame('Podium FLUOR', $user->organizer_name);
    }

    public function test_organizer_name_falls_back_to_the_username(): void
    {
        $user = User::factory()->make(['name' => '', 'username' => 'podiumfluor']);

        $this->assertSame('podiumfluor', $user->organizer_name);
    }

    public function test_organizer_name_falls_back_to_the_username_when_name_is_null(): void
    {
        $user = User::factory()->make(['name' => null, 'username' => 'podiumfluor']);

        $this->assertSame('podiumfluor', $user->organizer_name);
    }
}
