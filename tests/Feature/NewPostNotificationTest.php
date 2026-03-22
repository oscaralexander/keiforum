<?php

namespace Tests\Feature;

use App\Events\PostCreated;
use App\Listeners\SendNewPostNotification;
use App\Mail\NewPostInTopic;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewPostNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_is_sent_to_subscribers(): void
    {
        Mail::fake();

        $author = User::factory()->create();
        $subscriber = User::factory()->create();
        $topic = Topic::factory()->create();
        $topic->trackedByUsers()->attach($subscriber->id, ['is_subscribed' => true]);

        $post = Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $author->id]);

        $listener = new SendNewPostNotification;
        $listener->handle(new PostCreated($post));

        Mail::assertSent(NewPostInTopic::class, fn ($mail) => $mail->hasTo($subscriber->email));
    }

    public function test_notification_is_not_sent_to_post_author(): void
    {
        Mail::fake();

        $author = User::factory()->create();
        $topic = Topic::factory()->create();
        $topic->trackedByUsers()->attach($author->id, ['is_subscribed' => true]);

        $post = Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $author->id]);

        $listener = new SendNewPostNotification;
        $listener->handle(new PostCreated($post));

        Mail::assertNotSent(NewPostInTopic::class, fn ($mail) => $mail->hasTo($author->email));
    }

    public function test_notification_is_not_sent_to_non_subscribers(): void
    {
        Mail::fake();

        $author = User::factory()->create();
        $watcher = User::factory()->create();
        $topic = Topic::factory()->create();
        $topic->trackedByUsers()->attach($watcher->id, ['is_subscribed' => false]);

        $post = Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $author->id]);

        $listener = new SendNewPostNotification;
        $listener->handle(new PostCreated($post));

        Mail::assertNotSent(NewPostInTopic::class);
    }

    public function test_notification_is_not_sent_when_already_notified_about_unread_post(): void
    {
        Mail::fake();

        $author = User::factory()->create();
        $subscriber = User::factory()->create();
        $topic = Topic::factory()->create();
        $previousPost = Post::factory()->create(['topic_id' => $topic->id]);

        $topic->trackedByUsers()->attach($subscriber->id, [
            'is_subscribed' => true,
            'last_read_post_id' => null,
            'last_notified_post_id' => $previousPost->id,
        ]);

        $post = Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $author->id]);

        $listener = new SendNewPostNotification;
        $listener->handle(new PostCreated($post));

        Mail::assertNotSent(NewPostInTopic::class);
    }

    public function test_notification_is_sent_when_user_has_read_since_last_notification(): void
    {
        Mail::fake();

        $author = User::factory()->create();
        $subscriber = User::factory()->create();
        $topic = Topic::factory()->create();
        $previousPost = Post::factory()->create(['topic_id' => $topic->id]);

        $topic->trackedByUsers()->attach($subscriber->id, [
            'is_subscribed' => true,
            'last_read_post_id' => $previousPost->id,
            'last_notified_post_id' => $previousPost->id,
        ]);

        $post = Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $author->id]);

        $listener = new SendNewPostNotification;
        $listener->handle(new PostCreated($post));

        Mail::assertSent(NewPostInTopic::class, fn ($mail) => $mail->hasTo($subscriber->email));
    }

    public function test_last_notified_post_id_is_updated_after_sending(): void
    {
        Mail::fake();

        $author = User::factory()->create();
        $subscriber = User::factory()->create();
        $topic = Topic::factory()->create();
        $topic->trackedByUsers()->attach($subscriber->id, ['is_subscribed' => true]);

        $post = Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $author->id]);

        $listener = new SendNewPostNotification;
        $listener->handle(new PostCreated($post));

        $pivot = $topic->trackedByUsers()->where('user_id', $subscriber->id)->first()->pivot;
        $this->assertEquals($post->id, $pivot->last_notified_post_id);
    }

    public function test_only_first_new_post_triggers_notification(): void
    {
        Mail::fake();

        $author = User::factory()->create();
        $subscriber = User::factory()->create();
        $topic = Topic::factory()->create();
        $topic->trackedByUsers()->attach($subscriber->id, ['is_subscribed' => true]);

        $firstPost = Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $author->id]);
        $listener = new SendNewPostNotification;
        $listener->handle(new PostCreated($firstPost));

        $secondPost = Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $author->id]);
        $listener->handle(new PostCreated($secondPost));

        Mail::assertSent(NewPostInTopic::class, 1);
    }
}
