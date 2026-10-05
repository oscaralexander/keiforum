<?php

namespace Tests\Feature;

use App\Lib\Image;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ImageFormatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $image = imagecreatetruecolor(40, 40);
        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();

        Http::fake(['example.com/*' => Http::response($png, 200, ['Content-Type' => 'image/png'])]);
    }

    public function test_image_proxy_serves_webp_by_default(): void
    {
        $response = $this->get(route('img', ['src' => 'https://example.com/a.png', 'w' => 20, 'h' => 20]));

        $response->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->assertSame('RIFF', substr($response->getContent(), 0, 4));
    }

    public function test_image_proxy_serves_jpeg_on_request(): void
    {
        $response = $this->get(route('img', ['src' => 'https://example.com/a.png', 'w' => 20, 'h' => 20, 'f' => 'jpg']));

        $response->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame("\xFF\xD8\xFF", substr($response->getContent(), 0, 3));
        Storage::disk('public')->assertExists(Image::cacheFilePath('https://example.com/a.png', 20, 20, 'jpg'));
    }

    public function test_image_proxy_rejects_unknown_format(): void
    {
        $this->getJson(route('img', ['src' => 'https://example.com/a.png', 'f' => 'gif']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('f');
    }

    public function test_email_avatar_url_uses_jpeg_for_uploaded_avatars(): void
    {
        $user = User::factory()->create(['username' => 'anne', 'has_avatar' => true]);

        $this->assertSame(
            route('img', ['src' => 'avatars/anne.webp', 'w' => 128, 'h' => 128, 'q' => 80, 'f' => 'jpg']),
            $user->emailAvatarUrl(128),
        );
    }

    public function test_email_avatar_url_uses_png_for_default_avatars(): void
    {
        $user = User::factory()->create(['username' => 'bas', 'has_avatar' => false]);

        $this->assertSame(asset('assets/img/avatar/b.png'), $user->emailAvatarUrl());
        $this->assertFileExists(public_path('assets/img/avatar/b.png'));
    }

    public function test_removing_avatar_clears_cached_jpeg_copies(): void
    {
        $user = User::factory()->create(['username' => 'cor', 'has_avatar' => true]);
        $jpegCachePath = Image::cacheFilePath($user->avatar, 128, 128, 'jpg');
        Storage::disk('public')->put($jpegCachePath, 'jpeg');
        Storage::disk('public')->put($user->avatar, 'webp');

        Livewire::actingAs($user)
            ->test('pages::user.profile')
            ->call('removeAvatar');

        Storage::disk('public')->assertMissing($jpegCachePath);
    }

    public function test_cached_image_is_served_without_fetching_it_again(): void
    {
        $url = route('img', ['src' => 'https://example.com/a.png', 'w' => 20, 'h' => 20]);

        $first = $this->get($url)->assertOk()->getContent();
        $second = $this->get($url)->assertOk()->getContent();

        $this->assertSame($first, $second);
        Http::assertSentCount(1);
    }

    public function test_expired_cache_is_regenerated(): void
    {
        $url = route('img', ['src' => 'https://example.com/a.png', 'w' => 20, 'h' => 20]);
        $this->get($url)->assertOk();

        touch(Storage::disk('public')->path(Image::cacheFilePath('https://example.com/a.png', 20, 20)), now()->subSeconds(Image::CACHE_TTL + 60)->getTimestamp());

        $this->get($url)->assertOk();

        Http::assertSentCount(2);
    }
}
