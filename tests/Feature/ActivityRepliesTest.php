<?php

namespace Tests\Feature;

use App\Events\PostCreated;
use App\Listeners\SendNewPostNotification;
use App\Mail\NewPostInTopic;
use App\Models\Activity;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityRepliesTest extends TestCase
{
    use RefreshDatabase;

    private function createActivityWithReply(): array
    {
        $activity = Activity::factory()->create();
        $topic = $activity->findOrCreateTopic();
        $post = Post::factory()->create(['topic_id' => $topic->id]);

        return [$activity, $topic, $post];
    }

    public function test_guest_sees_the_login_text_instead_of_the_reply_form(): void
    {
        $activity = Activity::factory()->create();

        $this->get($activity->url())
            ->assertOk()
            ->assertSee('om te reageren.', false)
            ->assertDontSee('wire:submit="submit"', false);
    }

    public function test_member_sees_the_reply_form(): void
    {
        $activity = Activity::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get($activity->url())
            ->assertOk()
            ->assertSee('wire:submit="submit"', false);
    }

    public function test_first_reply_creates_a_hidden_topic_and_subscribes_the_venue(): void
    {
        $activity = Activity::factory()->create();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('replies', ['activity' => $activity, 'topic' => null])
            ->set('body', '<p>Is er een garderobe?</p>')
            ->call('submit');

        $topic = $activity->refresh()->topic;

        $this->assertNotNull($topic);
        $this->assertFalse($topic->is_visible);
        $this->assertSame($activity->title, $topic->title);
        $this->assertSame($activity->user_id, $topic->user_id);
        $this->assertTrue($topic->forum->is_hidden);
        $this->assertSame(config('agenda.forum_slug'), $topic->forum->slug);
        $this->assertTrue($topic->subscribers()->where('users.id', $activity->user_id)->exists());
        $this->assertTrue($topic->subscribers()->where('users.id', $user->id)->exists());
        $this->assertDatabaseHas('posts', ['topic_id' => $topic->id, 'user_id' => $user->id, 'body' => '<p>Is er een garderobe?</p>']);
    }

    public function test_reply_redirects_to_the_activity_with_the_post(): void
    {
        $activity = Activity::factory()->create();

        $component = Livewire::actingAs(User::factory()->create())
            ->test('replies', ['activity' => $activity, 'topic' => null])
            ->set('body', '<p>Leuk!</p>')
            ->call('submit');

        $post = Post::query()->latest('id')->first();

        $component->assertRedirect($activity->url(['post' => $post->id]));
    }

    public function test_second_reply_reuses_the_topic(): void
    {
        [$activity, $topic] = $this->createActivityWithReply();

        Livewire::actingAs(User::factory()->create())
            ->test('replies', ['activity' => $activity, 'topic' => $topic])
            ->set('body', '<p>Ik ook!</p>')
            ->call('submit');

        $this->assertSame(1, Topic::query()->where('activity_id', $activity->id)->count());
        $this->assertSame(2, $topic->posts()->count());
    }

    public function test_guest_cannot_reply(): void
    {
        $activity = Activity::factory()->create();

        Livewire::test('replies', ['activity' => $activity, 'topic' => null])
            ->set('body', '<p>Hallo</p>')
            ->call('submit')
            ->assertForbidden();

        $this->assertNull($activity->refresh()->topic);
    }

    public function test_activity_page_shows_the_replies_without_opening_post_controls(): void
    {
        [$activity, , $post] = $this->createActivityWithReply();

        $this->get($activity->url())
            ->assertOk()
            ->assertSee('id="post-'.$post->id.'"', false)
            ->assertSee($activity->url(['post' => $post->id]), false);

        $replies = Livewire::test('replies', ['activity' => $activity, 'topic' => $activity->topic])->instance();

        $this->assertFalse($replies->isFirstPost($post));
    }

    public function test_activity_page_without_replies_has_no_topic(): void
    {
        $activity = Activity::factory()->create();

        $this->actingAs(User::factory()->create())->get($activity->url())->assertOk();

        $this->assertNull($activity->refresh()->topic);
    }

    public function test_post_deep_link_opens_the_page_with_the_post(): void
    {
        [$activity, $topic] = $this->createActivityWithReply();
        $posts = Post::factory()->count(Post::PAGINATE_COUNT)->create(['topic_id' => $topic->id]);

        $this->get($activity->url(['post' => $posts->last()->id]))
            ->assertOk()
            ->assertSee('id="post-'.$posts->last()->id.'"', false);
    }

    public function test_topic_url_points_to_the_activity(): void
    {
        [$activity, $topic, $post] = $this->createActivityWithReply();

        $this->assertSame($activity->url(['post' => $post->id]), $topic->url(['post' => $post->id]));
    }

    public function test_direct_topic_url_redirects_to_the_activity(): void
    {
        [$activity, $topic, $post] = $this->createActivityWithReply();

        $this->get(route('topic.show', [$topic->forum, $topic, $topic->slug, 'post' => $post->id]))
            ->assertRedirect($activity->url(['post' => $post->id]))
            ->assertStatus(301);
    }

    public function test_new_post_mail_links_to_the_activity(): void
    {
        Mail::fake();

        [$activity, $topic] = $this->createActivityWithReply();
        $reply = Post::factory()->create(['topic_id' => $topic->id]);

        (new SendNewPostNotification)->handle(new PostCreated($reply));

        Mail::assertSent(NewPostInTopic::class, function (NewPostInTopic $mail) use ($activity, $reply): bool {
            return $mail->hasTo($activity->user->email)
                && str_contains($mail->render(), e($activity->url(['post' => $reply->id])));
        });
    }

    public function test_changing_the_activity_title_renames_the_topic(): void
    {
        [$activity, $topic] = $this->createActivityWithReply();

        $activity->update(['title' => 'Nieuwe titel']);

        $this->assertSame('Nieuwe titel', $topic->refresh()->title);
    }

    public function test_deleting_the_activity_deletes_the_topic(): void
    {
        [$activity, $topic] = $this->createActivityWithReply();

        $activity->delete();

        $this->assertSoftDeleted($topic);
    }

    public function test_agenda_forum_is_hidden_from_listings(): void
    {
        $forum = Forum::agenda();
        $user = User::factory()->create();

        $this->get(route('home'))->assertOk()->assertDontSee(route('forum.show', $forum), false);
        $this->get(route('sitemap'))->assertOk()->assertDontSee(route('forum.show', $forum), false);
        $this->get(route('forum.show', $forum))->assertNotFound();

        $this->assertFalse($user->can('createTopic', $forum));
        $this->assertFalse(User::factory()->admin()->create()->can('createTopic', $forum));
        $this->assertFalse(Forum::query()->listed()->whereKey($forum->id)->exists());
    }
}
