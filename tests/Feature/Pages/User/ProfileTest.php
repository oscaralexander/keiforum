<?php

namespace Tests\Feature\Pages\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_visit_profile_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk();
    }

    public function test_guest_cannot_visit_profile_page(): void
    {
        $this->get(route('profile'))
            ->assertRedirect(route('login'));
    }

    public function test_submitting_profile_dispatches_toast_event(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        Livewire::actingAs($user)
            ->test('pages::user.profile')
            ->set('name', 'New Name')
            ->call('submit')
            ->assertDispatched('toast');
    }

    public function test_submitting_profile_saves_user_data(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        Livewire::actingAs($user)
            ->test('pages::user.profile')
            ->set('name', 'New Name')
            ->set('bio', 'Hello world')
            ->call('submit');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'bio' => 'Hello world',
        ]);
    }
}
