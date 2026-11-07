<?php

namespace Tests\Feature;

use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomeForumPanelsTest extends TestCase
{
    use RefreshDatabase;

    private function createForumWithTopics(int $topicCount): Forum
    {
        $forum = Forum::factory()->create(['name' => 'Testforum', 'description' => 'Een forum om te testen.']);

        Topic::factory()
            ->count($topicCount)
            ->for($forum)
            ->create()
            ->each(fn (Topic $topic) => Post::factory()->for($topic)->create());

        return $forum;
    }

    public function test_home_page_renders_each_forum_as_a_panel_with_topic_list_items(): void
    {
        $forum = $this->createForumWithTopics(2);
        $topic = $forum->topics()->first();

        Livewire::test('pages::index')
            ->assertSeeHtml('class="panel forumListItem"')
            ->assertSeeHtml('class="topicListItem"')
            ->assertSeeInOrder(['Testforum', 'Een forum om te testen.', $topic->title]);
    }

    public function test_home_page_shows_remaining_topic_count_below_the_list(): void
    {
        $this->createForumWithTopics(7);

        Livewire::test('pages::index')
            ->assertSee(trans_choice('forum/index.more_topics', 2, ['count' => 2]));
    }

    public function test_home_page_hides_remaining_topic_count_when_all_topics_are_shown(): void
    {
        $this->createForumWithTopics(3);

        Livewire::test('pages::index')
            ->assertDontSeeHtml('forumListItem__moreTopics');
    }
}
