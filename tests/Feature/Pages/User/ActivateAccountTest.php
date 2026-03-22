<?php

namespace Tests\Feature\Pages\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivateAccountTest extends TestCase
{
    use RefreshDatabase;

    private function makeUnverifiedUser(): User
    {
        return User::factory()->create([
            'email_verified_at' => null,
            'email_verification_token' => 'valid-token-abc123',
        ]);
    }

    public function test_visiting_activation_link_shows_confirmation_page_without_activating(): void
    {
        $user = $this->makeUnverifiedUser();

        $this->get(route('activate-account', ['token' => 'valid-token-abc123']))
            ->assertOk();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_confirmation_page_shows_activate_button_for_valid_token(): void
    {
        $this->makeUnverifiedUser();

        Livewire::test('pages::user.activate-account', ['token' => 'valid-token-abc123'])
            ->assertSet('isValidToken', true)
            ->assertSet('success', false);
    }

    public function test_calling_activate_action_verifies_the_user(): void
    {
        $user = $this->makeUnverifiedUser();

        Livewire::test('pages::user.activate-account', ['token' => 'valid-token-abc123'])
            ->call('activate')
            ->assertSet('success', true)
            ->assertSet('isValidToken', false);

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNull($user->fresh()->email_verification_token);
    }

    public function test_invalid_token_shows_error_state(): void
    {
        Livewire::test('pages::user.activate-account', ['token' => 'nonexistent-token'])
            ->assertSet('isValidToken', false)
            ->assertSet('success', false);
    }

    public function test_already_verified_user_sees_success_state_on_page_load(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'email_verification_token' => 'old-token',
        ]);

        Livewire::test('pages::user.activate-account', ['token' => 'old-token'])
            ->assertSet('success', true)
            ->assertSet('isValidToken', false);
    }

    public function test_prefetch_does_not_activate_account(): void
    {
        $user = $this->makeUnverifiedUser();

        // Simulate Outlook Safe Links prefetching the URL multiple times
        $this->get(route('activate-account', ['token' => 'valid-token-abc123']));
        $this->get(route('activate-account', ['token' => 'valid-token-abc123']));

        $this->assertNull($user->fresh()->email_verified_at);
    }
}
