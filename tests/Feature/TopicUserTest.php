<?php

namespace Tests\Feature;

use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\QueryException;
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

        $this->expectException(QueryException::class);

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

    public function test_subscribe_defaults_to_true_without_pivot(): void
    {
        $topic = Topic::factory()->create();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::topic.show', ['topic' => $topic])
            ->assertSet('subscribe', true);
    }

    public function test_subscribe_defaults_to_true_when_pivot_has_no_choice(): void
    {
        $topic = Topic::factory()->create();
        $user = User::factory()->create();

        $topic->trackedByUsers()->attach($user->id);

        Livewire::actingAs($user)
            ->test('pages::topic.show', ['topic' => $topic])
            ->assertSet('subscribe', true);
    }

    public function test_viewing_topic_does_not_subscribe_user(): void
    {
        $topic = Topic::factory()->create();
        Post::factory()->create(['topic_id' => $topic->id]);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::topic.show', ['topic' => $topic]);

        $this->assertFalse($topic->subscribers()->where('users.id', $user->id)->exists());
    }

    public function test_subscribe_is_false_when_user_opted_out(): void
    {
        $topic = Topic::factory()->create();
        $user = User::factory()->create();

        $topic->trackedByUsers()->attach($user->id, ['is_subscribed' => false]);

        Livewire::actingAs($user)
            ->test('pages::topic.show', ['topic' => $topic])
            ->assertSet('subscribe', false);
    }

    public function test_replying_subscribes_user_by_default(): void
    {
        $topic = Topic::factory()->create();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::topic.show', ['topic' => $topic])
            ->set('body', '<p>Reactie</p>')
            ->call('submit');

        $this->assertTrue($topic->subscribers()->where('users.id', $user->id)->exists());
    }

    public function test_replying_keeps_user_unsubscribed_after_opting_out(): void
    {
        $topic = Topic::factory()->create();
        $user = User::factory()->create();

        $topic->trackedByUsers()->attach($user->id, ['is_subscribed' => false]);

        Livewire::actingAs($user)
            ->test('pages::topic.show', ['topic' => $topic])
            ->set('body', '<p>Reactie</p>')
            ->call('submit');

        $this->assertFalse($topic->subscribers()->where('users.id', $user->id)->exists());
    }

    public function test_creating_topic_subscribes_user_by_default(): void
    {
        $forum = Forum::factory()->create();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::topic.create', ['forum' => $forum])
            ->set('title', 'Nieuw onderwerp')
            ->set('body', '<p>Test</p>')
            ->call('submit');

        $topic = Topic::query()->where('title', 'Nieuw onderwerp')->firstOrFail();

        $this->assertTrue($topic->subscribers()->where('users.id', $user->id)->exists());
    }

    public function test_creating_topic_without_subscribing(): void
    {
        $forum = Forum::factory()->create();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::topic.create', ['forum' => $forum])
            ->assertSet('subscribe', true)
            ->set('title', 'Nieuw onderwerp')
            ->set('body', '<p>Test</p>')
            ->set('subscribe', false)
            ->call('submit');

        $topic = Topic::query()->where('title', 'Nieuw onderwerp')->firstOrFail();

        $this->assertFalse($topic->subscribers()->where('users.id', $user->id)->exists());
    }
}
