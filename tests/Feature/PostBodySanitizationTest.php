<?php

namespace Tests\Feature;

use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PostBodySanitizationTest extends TestCase
{
    use RefreshDatabase;

    private function createPost(string $body): Post
    {
        $forum = Forum::factory()->create();
        $topic = Topic::factory()->create(['forum_id' => $forum->id]);

        return Post::factory()->create(['topic_id' => $topic->id, 'body' => $body]);
    }

    public function test_scripts_and_event_handlers_are_not_rendered(): void
    {
        $post = $this->createPost('<p onmouseover="alert(1)">Hallo</p><script>alert(2)</script><img src="x" onerror="alert(3)">');

        $this->get(route('topic.show', [$post->topic->forum, $post->topic, $post->topic->slug]))
            ->assertOk()
            ->assertSee('Hallo')
            ->assertDontSee('alert(1)', false)
            ->assertDontSee('alert(2)', false)
            ->assertDontSee('alert(3)', false);
    }

    public function test_javascript_links_lose_their_href(): void
    {
        $post = $this->createPost('<p><a href="javascript:alert(1)">klik</a></p>');

        $this->assertStringNotContainsString('javascript:', $post->body_transformed);
    }

    public function test_stored_body_is_left_unchanged(): void
    {
        $body = '<p onclick="x">Hallo</p>';
        $post = $this->createPost($body);

        $this->assertSame($body, $post->fresh()->body);
    }

    public function test_mentions_embeds_and_formatting_survive(): void
    {
        $post = $this->createPost(
            '<p><strong>Hoi</strong> <a href="https://keiforum.nl/@jan" data-mention="true">@jan</a></p>'
            .'<p><a href="https://www.youtube.com/watch?v=dQw4w9WgXcQ" data-embed="true">video</a></p>'
            .'<ol><li>Een</li></ol><blockquote><p>Citaat</p></blockquote>'
        );

        $html = $post->body_transformed;

        $this->assertStringContainsString('<strong>Hoi</strong>', $html);
        $this->assertStringContainsString('data-mention="true"', $html);
        $this->assertStringContainsString('youtube.com/embed/dQw4w9WgXcQ', $html);
        $this->assertStringContainsString('<ol><li>Een</li></ol>', $html);
        $this->assertStringContainsString('<blockquote><p>Citaat</p></blockquote>', $html);
    }

    public function test_external_links_still_open_in_new_window_and_internal_links_navigate(): void
    {
        $post = $this->createPost('<p><a href="https://example.com">extern</a> <a href="'.url('/leden').'">intern</a></p>');

        $html = $post->body_transformed;

        $this->assertStringContainsString('href="https://example.com" rel="nofollow ugc noopener" target="_blank"', $html);
        $this->assertStringContainsString('href="'.url('/leden').'" wire:navigate', $html);
    }

    public function test_editor_is_loaded_with_the_sanitized_body(): void
    {
        $post = $this->createPost('<p>Hallo</p><img src="x" onerror="alert(1)">');

        Livewire::actingAs(User::factory()->admin()->create())
            ->test('post', ['post' => $post, 'isFirstPost' => false, 'number' => 1])
            ->assertSet('body', '<p>Hallo</p>');
    }
}
