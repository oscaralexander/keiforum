<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private function mockSocialiteUser(array $overrides = []): SocialiteUser
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn($overrides['id'] ?? '987654321');
        $socialiteUser->shouldReceive('getEmail')->andReturn($overrides['email'] ?? 'john@example.com');
        $socialiteUser->shouldReceive('getName')->andReturn($overrides['name'] ?? 'John Doe');
        $socialiteUser->shouldReceive('getAvatar')->andReturn($overrides['avatar'] ?? null);

        return $socialiteUser;
    }

    private function mockSocialite(SocialiteUser $socialiteUser): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        $socialite = Mockery::mock(SocialiteFactory::class);
        $socialite->shouldReceive('driver')->with('google')->andReturn($provider);

        $this->app->instance(SocialiteFactory::class, $socialite);
    }

    public function test_redirect_route_redirects_to_google(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

        $socialite = Mockery::mock(SocialiteFactory::class);
        $socialite->shouldReceive('driver')->with('google')->andReturn($provider);

        $this->app->instance(SocialiteFactory::class, $socialite);

        $response = $this->get(route('auth.google'));

        $response->assertRedirect();
    }

    public function test_callback_stores_google_data_in_session_for_new_user(): void
    {
        $this->mockSocialite($this->mockSocialiteUser());

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('register-oauth'));
        $this->assertEquals('john@example.com', session('oauth.google.email'));
        $this->assertEquals('987654321', session('oauth.google.id'));
        $this->assertEquals('John Doe', session('oauth.google.name'));
    }

    public function test_callback_does_not_create_user_for_new_user(): void
    {
        $this->mockSocialite($this->mockSocialiteUser());

        $this->get(route('auth.google.callback'));

        $this->assertDatabaseMissing('users', ['email' => 'john@example.com']);
    }

    public function test_callback_logs_in_existing_user_by_google_id(): void
    {
        $existingUser = User::factory()->google('987654321')->create([
            'email' => 'john@example.com',
        ]);

        $this->mockSocialite($this->mockSocialiteUser());

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($existingUser);
    }

    public function test_callback_logs_in_existing_user_by_email_and_sets_google_id(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'john@example.com',
            'google_id' => null,
        ]);

        $this->mockSocialite($this->mockSocialiteUser());

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($existingUser);
        $this->assertDatabaseHas('users', [
            'id' => $existingUser->id,
            'google_id' => '987654321',
        ]);
    }
}
