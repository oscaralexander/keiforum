<?php

namespace Tests\Feature\Pages\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithToken(): array
    {
        $user = User::factory()->create([
            'email' => 'lid@keiforum.nl',
            'password' => Hash::make('oud-wachtwoord'),
        ]);

        return [$user, Password::createToken($user)];
    }

    public function test_valid_token_shows_the_form(): void
    {
        [$user, $token] = $this->makeUserWithToken();

        Livewire::withQueryParams(['email' => $user->email])
            ->test('pages::user.reset-password', ['token' => $token])
            ->assertSet('isValidToken', true);
    }

    public function test_visiting_the_link_does_not_consume_the_token(): void
    {
        [$user, $token] = $this->makeUserWithToken();

        $this->get(route('reset-password', ['token' => $token, 'email' => $user->email]))
            ->assertOk();

        $this->assertTrue(Password::getRepository()->exists($user->fresh(), $token));
    }

    public function test_submitting_a_new_password_updates_it_and_logs_the_user_in(): void
    {
        [$user, $token] = $this->makeUserWithToken();

        Livewire::withQueryParams(['email' => $user->email])
            ->test('pages::user.reset-password', ['token' => $token])
            ->set('password', 'Nieuw-Wachtwoord1')
            ->call('submit')
            ->assertRedirect(route('home'));

        $this->assertTrue(Hash::check('Nieuw-Wachtwoord1', $user->fresh()->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_token_is_consumed_after_a_successful_reset(): void
    {
        [$user, $token] = $this->makeUserWithToken();

        Livewire::withQueryParams(['email' => $user->email])
            ->test('pages::user.reset-password', ['token' => $token])
            ->set('password', 'Nieuw-Wachtwoord1')
            ->call('submit');

        $this->assertFalse(Password::getRepository()->exists($user->fresh(), $token));
    }

    public function test_weak_password_is_rejected(): void
    {
        [$user, $token] = $this->makeUserWithToken();

        Livewire::withQueryParams(['email' => $user->email])
            ->test('pages::user.reset-password', ['token' => $token])
            ->set('password', 'kort')
            ->call('submit')
            ->assertHasErrors('password');

        $this->assertTrue(Hash::check('oud-wachtwoord', $user->fresh()->password));
        $this->assertGuest();
    }

    public function test_invalid_token_shows_error_state(): void
    {
        [$user] = $this->makeUserWithToken();

        Livewire::withQueryParams(['email' => $user->email])
            ->test('pages::user.reset-password', ['token' => 'onzin-token'])
            ->assertSet('isValidToken', false);
    }

    public function test_unknown_email_shows_error_state(): void
    {
        [, $token] = $this->makeUserWithToken();

        Livewire::withQueryParams(['email' => 'niemand@keiforum.nl'])
            ->test('pages::user.reset-password', ['token' => $token])
            ->assertSet('isValidToken', false);
    }

    public function test_expired_token_shows_error_state(): void
    {
        [$user, $token] = $this->makeUserWithToken();

        $this->travel(config('auth.passwords.users.expire') + 1)->minutes();

        Livewire::withQueryParams(['email' => $user->email])
            ->test('pages::user.reset-password', ['token' => $token])
            ->assertSet('isValidToken', false);
    }
}
