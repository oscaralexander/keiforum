<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterOauthTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{id: string, email: string, name: string, avatar: null} */
    private function oauthSession(array $overrides = []): array
    {
        return array_merge([
            'id' => '987654321',
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'avatar' => null,
        ], $overrides);
    }

    public function test_page_redirects_to_login_without_session_data(): void
    {
        $this->get(route('register-oauth'))
            ->assertRedirect(route('login'));
    }

    public function test_page_renders_with_valid_session(): void
    {
        $this->withSession(['oauth.google' => $this->oauthSession()])
            ->get(route('register-oauth'))
            ->assertOk();
    }

    public function test_submit_creates_user_with_required_username(): void
    {
        session(['oauth.google' => $this->oauthSession()]);

        Livewire::test('pages::user.register-oauth')
            ->set('username', 'johndoe')
            ->call('submit');

        $this->assertDatabaseHas('users', [
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'google_id' => '987654321',
        ]);
    }

    public function test_submit_lowercases_username(): void
    {
        session(['oauth.google' => $this->oauthSession()]);

        Livewire::test('pages::user.register-oauth')
            ->set('username', 'JohnDoe')
            ->call('submit');

        $this->assertDatabaseHas('users', ['username' => 'johndoe']);
    }

    public function test_submit_sets_email_verified_at(): void
    {
        session(['oauth.google' => $this->oauthSession()]);

        Livewire::test('pages::user.register-oauth')
            ->set('username', 'johndoe')
            ->call('submit');

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_submit_logs_in_user(): void
    {
        session(['oauth.google' => $this->oauthSession()]);

        Livewire::test('pages::user.register-oauth')
            ->set('username', 'johndoe')
            ->call('submit');

        $this->assertAuthenticated();
    }

    public function test_submit_redirects_to_home(): void
    {
        session(['oauth.google' => $this->oauthSession()]);

        Livewire::test('pages::user.register-oauth')
            ->set('username', 'johndoe')
            ->call('submit')
            ->assertRedirect(route('home'));
    }

    public function test_submit_clears_oauth_session(): void
    {
        session(['oauth.google' => $this->oauthSession()]);

        Livewire::test('pages::user.register-oauth')
            ->set('username', 'johndoe')
            ->call('submit');

        $this->assertNull(session('oauth.google'));
    }

    public function test_submit_stores_optional_fields(): void
    {
        session(['oauth.google' => $this->oauthSession()]);

        Livewire::test('pages::user.register-oauth')
            ->set('username', 'johndoe')
            ->set('birthdate', '1990-05-15')
            ->set('gender', 'male')
            ->call('submit');

        $user = User::where('username', 'johndoe')->first();
        $this->assertEquals('1990-05-15', $user->birthdate->format('Y-m-d'));
        $this->assertEquals('male', $user->gender->value);
        $this->assertDatabaseHas('users', ['username' => 'johndoe']);
    }

    public function test_username_is_required(): void
    {
        session(['oauth.google' => $this->oauthSession()]);

        Livewire::test('pages::user.register-oauth')
            ->set('username', '')
            ->call('submit')
            ->assertHasErrors(['username' => 'required']);
    }

    public function test_username_must_be_unique(): void
    {
        User::factory()->create(['username' => 'johndoe']);

        session(['oauth.google' => $this->oauthSession()]);

        Livewire::test('pages::user.register-oauth')
            ->set('username', 'johndoe')
            ->call('submit')
            ->assertHasErrors(['username' => 'unique']);
    }

    public function test_username_must_be_max_16_characters(): void
    {
        session(['oauth.google' => $this->oauthSession()]);

        Livewire::test('pages::user.register-oauth')
            ->set('username', 'averylongusername')
            ->call('submit')
            ->assertHasErrors(['username' => 'max']);
    }

    public function test_username_availability_updates_live(): void
    {
        session(['oauth.google' => $this->oauthSession()]);

        Livewire::test('pages::user.register-oauth')
            ->set('username', 'johndoe')
            ->assertSet('usernameAvailable', true);
    }

    public function test_taken_username_is_not_available(): void
    {
        User::factory()->create(['username' => 'johndoe']);

        session(['oauth.google' => $this->oauthSession()]);

        Livewire::test('pages::user.register-oauth')
            ->set('username', 'johndoe')
            ->assertSet('usernameAvailable', false);
    }
}
