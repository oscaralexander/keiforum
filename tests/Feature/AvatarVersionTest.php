<?php

namespace Tests\Feature;

use App\Lib\OpenGraphImage;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AvatarVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_avatar_url_contains_avatar_version(): void
    {
        $user = User::factory()->create(['username' => 'anne', 'has_avatar' => true, 'avatar_updated_at' => '2026-10-06 10:00:00']);

        $this->assertStringContainsString('v='.$user->avatar_updated_at->timestamp, $user->avatarUrl(64));
        $this->assertStringContainsString('v='.$user->avatar_updated_at->timestamp, $user->emailAvatarUrl(64));
    }

    public function test_default_avatar_url_has_no_version(): void
    {
        $user = User::factory()->create(['username' => 'bas', 'has_avatar' => false, 'avatar_updated_at' => now()]);

        $this->assertSame('/assets/img/avatar/webp/b.webp', $user->avatarUrl(64));
    }

    public function test_uploading_an_avatar_changes_the_avatar_url(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['has_avatar' => true, 'avatar_updated_at' => now()->subDay()]);
        $oldUrl = $user->avatarUrl(64);

        $this->travel(1)->minutes();

        Livewire::actingAs($user)
            ->test('pages::user.profile')
            ->set('avatar', UploadedFile::fake()->image('nieuw.jpg', 300, 300));

        $user->refresh();
        $this->assertTrue($user->has_avatar);
        $this->assertNotSame($oldUrl, $user->avatarUrl(64));

        $this->actingAs($user)
            ->get(route('home'))
            ->assertSee(e($user->avatarUrl(64)), false);
    }

    public function test_removing_an_avatar_updates_the_avatar_version(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['has_avatar' => true, 'avatar_updated_at' => now()->subDay()]);

        Livewire::actingAs($user)->test('pages::user.profile')->call('removeAvatar');

        $user->refresh();
        $this->assertFalse($user->has_avatar);
        $this->assertTrue($user->avatar_updated_at->isToday());
    }

    public function test_visiting_pages_does_not_change_the_avatar_url(): void
    {
        $user = User::factory()->create(['has_avatar' => true, 'avatar_updated_at' => now()->subDay()]);
        $url = $user->avatarUrl(64);

        $this->travel(1)->minutes();
        $this->actingAs($user)->get(route('home'))->assertOk();

        $this->assertSame($url, $user->fresh()->avatarUrl(64));
    }

    public function test_open_graph_image_only_changes_with_the_avatar(): void
    {
        $user = User::factory()->create(['has_avatar' => true, 'avatar_updated_at' => now()->subDay()]);
        $topic = Topic::factory()->create(['user_id' => $user->id]);
        Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $user->id]);
        $openGraphImage = app(OpenGraphImage::class);
        $path = $openGraphImage->cachePath($topic->fresh());

        $this->travel(1)->minutes();
        $user->updateQuietly(['last_seen_at' => now()]);
        $this->assertSame($path, $openGraphImage->cachePath($topic->fresh()));

        $user->update(['avatar_updated_at' => now()]);
        $this->assertNotSame($path, $openGraphImage->cachePath($topic->fresh()));
    }
}
