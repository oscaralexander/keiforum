<?php

namespace Tests\Feature\Pages\User;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class RedirectAfterLoginTest extends TestCase
{
    use RefreshDatabase;

    private function topicUrl(): string
    {
        $topic = Topic::factory()->create();
        Post::factory()->create(['topic_id' => $topic->id]);

        return route('topic.show', [$topic->forum, $topic, $topic->slug]);
    }

    public function test_topic_page_login_link_contains_return_path(): void
    {
        $url = $this->topicUrl();

        $this->get($url)
            ->assertOk()
            ->assertSee(route('login', ['redirect' => parse_url($url, PHP_URL_PATH)]), false);
    }

    public function test_login_returns_to_redirect_path(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Geheim123')]);

        Livewire::withQueryParams(['redirect' => '/algemeen/1/een-topic'])
            ->test('pages::user.login')
            ->set('identifier', $user->email)
            ->set('password', 'Geheim123')
            ->call('submit')
            ->assertRedirect(url('/algemeen/1/een-topic'));
    }

    public function test_login_without_redirect_goes_home(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Geheim123')]);

        Livewire::test('pages::user.login')
            ->set('identifier', $user->email)
            ->set('password', 'Geheim123')
            ->call('submit')
            ->assertRedirect(route('home'));
    }

    public function test_external_redirect_is_ignored(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Geheim123')]);

        foreach (['https://evil.example', '//evil.example', '/\\evil.example'] as $redirect) {
            session()->forget('url.intended');

            Livewire::withQueryParams(['redirect' => $redirect])
                ->test('pages::user.login')
                ->set('identifier', $user->email)
                ->set('password', 'Geheim123')
                ->call('submit')
                ->assertRedirect(route('home'));

            auth()->logout();
        }
    }

    public function test_registration_stores_return_url_for_activation(): void
    {
        Mail::fake();

        Livewire::withQueryParams(['redirect' => '/algemeen/1/een-topic'])
            ->test('pages::user.register')
            ->set('email', 'nieuw@example.com')
            ->set('name', 'Nieuwe Gebruiker')
            ->set('password', 'Geheim123')
            ->set('terms', true)
            ->set('username', 'nieuwegebruiker')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(url('/algemeen/1/een-topic'), User::query()->where('email', 'nieuw@example.com')->value('intended_url'));
    }

    public function test_activation_returns_to_stored_url(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'email_verification_token' => 'valid-token-abc123',
            'intended_url' => url('/algemeen/1/een-topic'),
        ]);

        Livewire::test('pages::user.activate-account', ['token' => 'valid-token-abc123'])
            ->call('activate')
            ->assertRedirect(url('/algemeen/1/een-topic'));

        $this->assertNull($user->fresh()->intended_url);
        $this->assertAuthenticatedAs($user);
    }

    public function test_activation_without_stored_url_shows_success(): void
    {
        User::factory()->create([
            'email_verified_at' => null,
            'email_verification_token' => 'valid-token-abc123',
        ]);

        Livewire::test('pages::user.activate-account', ['token' => 'valid-token-abc123'])
            ->call('activate')
            ->assertNoRedirect()
            ->assertSet('success', true);
    }

    public function test_oauth_registration_returns_to_intended_url(): void
    {
        session([
            'url.intended' => url('/algemeen/1/een-topic'),
            'oauth.google' => [
                'avatar' => null,
                'email' => 'google@example.com',
                'id' => 'google-123',
                'name' => 'Google Gebruiker',
            ],
        ]);

        Livewire::test('pages::user.register-oauth')
            ->set('username', 'googlegebruiker')
            ->call('submit')
            ->assertRedirect(url('/algemeen/1/een-topic'));
    }
}
