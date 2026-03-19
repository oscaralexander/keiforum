<?php

namespace Tests\Feature;

use App\Events\PostSaving;
use App\Listeners\SendMentionNotification;
use App\Mail\UserMentioned;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class MentionNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_is_sent_to_mentioned_user_on_create(): void
    {
        Mail::fake();

        $author = User::factory()->create();
        $mentioned = User::factory()->create(['username' => 'janedoe']);
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'body' => '<p>Hey <a href="/@janedoe" data-mention="true">@janedoe</a>!</p>',
        ]);

        $listener = new SendMentionNotification;
        $listener->handle(new PostSaving($post));

        Mail::assertSent(UserMentioned::class, function (UserMentioned $mail) use ($mentioned, $post) {
            return $mail->hasTo($mentioned->email)
                && $mail->mentionedUser->is($mentioned)
                && $mail->post->is($post);
        });
    }

    public function test_no_email_is_sent_when_no_mentions(): void
    {
        Mail::fake();

        $post = Post::factory()->create([
            'body' => '<p>Just a regular post without any mentions.</p>',
        ]);

        $listener = new SendMentionNotification;
        $listener->handle(new PostSaving($post));

        Mail::assertNothingSent();
    }

    public function test_author_does_not_receive_mention_email_for_self_mention(): void
    {
        Mail::fake();

        $author = User::factory()->create(['username' => 'selfmention']);
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'body' => '<p><a href="/@selfmention" data-mention="true">@selfmention</a></p>',
        ]);

        $listener = new SendMentionNotification;
        $listener->handle(new PostSaving($post));

        Mail::assertNothingSent();
    }

    public function test_multiple_mentions_send_multiple_emails(): void
    {
        Mail::fake();

        $author = User::factory()->create();
        $userA = User::factory()->create(['username' => 'usera']);
        $userB = User::factory()->create(['username' => 'userb']);
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'body' => '<p><a href="/@usera" data-mention="true">@usera</a> and <a href="/@userb" data-mention="true">@userb</a></p>',
        ]);

        $listener = new SendMentionNotification;
        $listener->handle(new PostSaving($post));

        Mail::assertSentCount(2);
        Mail::assertSent(UserMentioned::class, fn ($m) => $m->hasTo($userA->email));
        Mail::assertSent(UserMentioned::class, fn ($m) => $m->hasTo($userB->email));
    }

    public function test_duplicate_mentions_only_send_one_email(): void
    {
        Mail::fake();

        $author = User::factory()->create();
        $mentioned = User::factory()->create(['username' => 'dupuser']);
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'body' => '<p><a href="/@dupuser" data-mention="true">@dupuser</a> again <a href="/@dupuser" data-mention="true">@dupuser</a></p>',
        ]);

        $listener = new SendMentionNotification;
        $listener->handle(new PostSaving($post));

        Mail::assertSentCount(1);
        Mail::assertSent(UserMentioned::class, fn ($m) => $m->hasTo($mentioned->email));
    }

    public function test_no_email_for_non_existent_mentioned_user(): void
    {
        Mail::fake();

        $post = Post::factory()->create([
            'body' => '<p><a href="/@ghostuser" data-mention="true">@ghostuser</a></p>',
        ]);

        $listener = new SendMentionNotification;
        $listener->handle(new PostSaving($post));

        Mail::assertNothingSent();
    }

    public function test_existing_mention_in_edit_does_not_resend_email(): void
    {
        Mail::fake();

        $author = User::factory()->create();
        $mentioned = User::factory()->create(['username' => 'alreadymentioned']);
        $oldBody = '<p><a href="/@alreadymentioned" data-mention="true">@alreadymentioned</a></p>';
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'body' => $oldBody,
        ]);

        $listener = new SendMentionNotification;
        $listener->handle(new PostSaving($post, $oldBody));

        Mail::assertNothingSent();
    }

    public function test_new_mention_added_in_edit_sends_email(): void
    {
        Mail::fake();

        $author = User::factory()->create();
        $existing = User::factory()->create(['username' => 'existing']);
        $added = User::factory()->create(['username' => 'newmentioned']);

        $oldBody = '<p><a href="/@existing" data-mention="true">@existing</a></p>';
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'body' => '<p><a href="/@existing" data-mention="true">@existing</a> and <a href="/@newmentioned" data-mention="true">@newmentioned</a></p>',
        ]);

        $listener = new SendMentionNotification;
        $listener->handle(new PostSaving($post, $oldBody));

        Mail::assertSentCount(1);
        Mail::assertSent(UserMentioned::class, fn ($m) => $m->hasTo($added->email));
    }

    public function test_post_saving_event_is_dispatched_on_create(): void
    {
        \Event::fake([PostSaving::class]);

        $author = User::factory()->create();
        $topic = Topic::factory()->create();
        Post::factory()->create(['topic_id' => $topic->id]);

        Livewire::actingAs($author)
            ->test('pages::topic.show', ['topic' => $topic])
            ->set('body', '<p>Hello world</p>')
            ->call('submit');

        \Event::assertDispatched(PostSaving::class, function (PostSaving $event) {
            return $event->oldBody === null;
        });
    }

    public function test_post_saving_event_is_dispatched_on_edit_with_old_body(): void
    {
        \Event::fake([PostSaving::class]);

        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id, 'body' => '<p>Original</p>']);

        Livewire::actingAs($author)
            ->test('post', ['post' => $post, 'number' => 1])
            ->call('edit')
            ->set('body', '<p>Updated</p>')
            ->call('submit');

        \Event::assertDispatched(PostSaving::class, function (PostSaving $event) use ($post) {
            return $event->post->is($post) && $event->oldBody === '<p>Original</p>';
        });
    }
}
