<?php

namespace Tests\Feature;

use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomeRecentTopicsTest extends TestCase
{
    use RefreshDatabase;

    private function createForumWithTopics(string $slug, int $topicCount): Forum
    {
        $forum = Forum::query()->where('slug', $slug)->first()
            ?? Forum::factory()->create(['name' => ucfirst($slug), 'slug' => $slug]);

        Topic::factory()
            ->count($topicCount)
            ->for($forum)
            ->create()
            ->each(fn (Topic $topic) => Post::factory()->for($topic)->create());

        return $forum;
    }

    public function test_general_forum_shows_ten_recent_topics(): void
    {
        $forum = $this->createForumWithTopics('algemeen', 12);

        $forums = Livewire::test('pages::index')->instance()->forums();

        $this->assertCount(10, $forums->firstWhere('id', $forum->id)->recentTopics);
    }

    public function test_other_forums_show_five_recent_topics(): void
    {
        $forum = $this->createForumWithTopics('hulp-en-handel', 12);

        $forums = Livewire::test('pages::index')->instance()->forums();

        $this->assertCount(5, $forums->firstWhere('id', $forum->id)->recentTopics);
    }
}
