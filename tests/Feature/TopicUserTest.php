<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TopicUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_attached_to_topic_with_pivot_data(): void
    {
        $topic = Topic::factory()->create();
        $user = User::factory()->create();

        $topic->trackedByUsers()->attach($user->id, [
            'is_subscribed' => true,
        ]);

        $pivot = $topic->trackedByUsers()->where('user_id', $user->id)->first()->pivot;

        $this->assertTrue($pivot->is_subscribed);
        $this->assertNull($pivot->last_read_post_id);
        $this->assertNull($pivot->last_notified_post_id);
    }

    public function test_last_read_post_id_can_be_updated(): void
    {
        $topic = Topic::factory()->create();
        $user = User::factory()->create();
        $post = Post::factory()->create(['topic_id' => $topic->id]);

        $topic->trackedByUsers()->attach($user->id, [
            'last_read_post_id' => $post->id,
        ]);

        $pivot = $topic->trackedByUsers()->where('user_id', $user->id)->first()->pivot;

        $this->assertEquals($post->id, $pivot->last_read_post_id);
    }

    public function test_last_notified_post_id_can_be_updated(): void
    {
        $topic = Topic::factory()->create();
        $user = User::factory()->create();
        $post = Post::factory()->create(['topic_id' => $topic->id]);

        $topic->trackedByUsers()->attach($user->id, [
            'last_notified_post_id' => $post->id,
        ]);

        $pivot = $topic->trackedByUsers()->where('user_id', $user->id)->first()->pivot;

        $this->assertEquals($post->id, $pivot->last_notified_post_id);
    }

    public function test_subscribers_only_returns_subscribed_users(): void
    {
        $topic = Topic::factory()->create();
        $subscriber = User::factory()->create();
        $nonSubscriber = User::factory()->create();

        $topic->trackedByUsers()->attach($subscriber->id, ['is_subscribed' => true]);
        $topic->trackedByUsers()->attach($nonSubscriber->id, ['is_subscribed' => false]);

        $subscriberIds = $topic->subscribers()->pluck('users.id');

        $this->assertTrue($subscriberIds->contains($subscriber->id));
        $this->assertFalse($subscriberIds->contains($nonSubscriber->id));
    }

    public function test_tracked_topics_accessible_from_user(): void
    {
        $topic = Topic::factory()->create();
        $user = User::factory()->create();

        $user->trackedTopics()->attach($topic->id, ['is_subscribed' => true]);

        $this->assertTrue($user->trackedTopics()->where('topic_id', $topic->id)->exists());
    }

    public function test_topic_user_pivot_is_unique_per_topic_and_user(): void
    {
        $topic = Topic::factory()->create();
        $user = User::factory()->create();

        $topic->trackedByUsers()->attach($user->id);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $topic->trackedByUsers()->attach($user->id);
    }

    public function test_toggling_subscribe_creates_pivot_record(): void
    {
        $topic = Topic::factory()->create();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::topic.show', ['topic' => $topic])
            ->set('subscribe', true);

        $pivot = $topic->trackedByUsers()->where('user_id', $user->id)->first()?->pivot;

        $this->assertNotNull($pivot);
        $this->assertTrue($pivot->is_subscribed);
    }

    public function test_toggling_unsubscribe_updates_pivot_record(): void
    {
        $topic = Topic::factory()->create();
        $user = User::factory()->create();

        $topic->trackedByUsers()->attach($user->id, ['is_subscribed' => true]);

        Livewire::actingAs($user)
            ->test('pages::topic.show', ['topic' => $topic])
            ->set('subscribe', false);

        $pivot = $topic->trackedByUsers()->where('user_id', $user->id)->first()?->pivot;

        $this->assertFalse($pivot->is_subscribed);
    }

    public function test_is_subscribed_is_initialized_from_pivot_on_mount(): void
    {
        $topic = Topic::factory()->create();
        $user = User::factory()->create();

        $topic->trackedByUsers()->attach($user->id, ['is_subscribed' => true]);

        Livewire::actingAs($user)
            ->test('pages::topic.show', ['topic' => $topic])
            ->assertSet('subscribe', true);
    }

    public function test_guest_cannot_toggle_subscribe(): void
    {
        $topic = Topic::factory()->create();

        Livewire::test('pages::topic.show', ['topic' => $topic])
            ->set('subscribe', true)
            ->assertForbidden();
    }
}
