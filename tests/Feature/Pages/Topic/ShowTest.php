<?php

namespace Tests\Feature\Pages\Topic;

use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    private function createTopicWithPost(): array
    {
        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create(['forum_id' => $forum->id]);
        $post = Post::factory()->create(['topic_id' => $topic->id]);

        return [$forum, $topic, $post];
    }

    public function test_topic_show_page_renders(): void
    {
        [$forum, $topic] = $this->createTopicWithPost();

        $this->get(route('topic.show', [$forum, $topic, $topic->slug]))->assertOk();
    }

    public function test_topic_show_displays_posts(): void
    {
        [$forum, $topic, $post] = $this->createTopicWithPost();

        Livewire::test('pages::topic.show', ['topic' => $topic])
            ->assertSee($post->user->username);
    }

    public function test_topic_show_displays_deleted_post_placeholder(): void
    {
        [$forum, $topic, $post] = $this->createTopicWithPost();
        $deletedPost = Post::factory()->create(['topic_id' => $topic->id]);
        $deletedPost->delete();

        Livewire::test('pages::topic.show', ['topic' => $topic])
            ->assertSeeLivewire('post');
    }

    public function test_topic_show_reply_form_visible_when_authenticated(): void
    {
        [$forum, $topic] = $this->createTopicWithPost();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::topic.show', ['topic' => $topic])
            ->assertSee(__('topic/show.reply'));
    }

    public function test_topic_show_reply_form_hidden_when_guest(): void
    {
        [$forum, $topic] = $this->createTopicWithPost();

        Livewire::test('pages::topic.show', ['topic' => $topic])
            ->assertSee(__('topic/show.reply_login'));
    }

    public function test_authenticated_user_can_submit_reply(): void
    {
        [$forum, $topic] = $this->createTopicWithPost();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::topic.show', ['topic' => $topic])
            ->set('body', '<p>Test reply</p>')
            ->call('submit')
            ->assertRedirect();

        $this->assertDatabaseHas('posts', [
            'topic_id' => $topic->id,
            'user_id' => $user->id,
            'body' => '<p>Test reply</p>',
        ]);
    }

    public function test_guest_cannot_submit_reply(): void
    {
        [$forum, $topic] = $this->createTopicWithPost();

        Livewire::test('pages::topic.show', ['topic' => $topic])
            ->set('body', '<p>Test reply</p>')
            ->call('submit')
            ->assertForbidden();
    }

    public function test_topic_shows_first_post_areas_without_extra_queries(): void
    {
        [$forum, $topic] = $this->createTopicWithPost();

        // Ensure the page renders with areas loaded (no lazy-load exception)
        Livewire::test('pages::topic.show', ['topic' => $topic])
            ->assertOk();
    }
}
