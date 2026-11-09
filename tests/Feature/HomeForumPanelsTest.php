<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Area;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
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

    public function test_home_page_hides_the_area_list_for_now(): void
    {
        Area::query()->create(['name' => 'Aaa-wijk', 'slug' => 'aaa-wijk']);

        Livewire::test('pages::index')
            ->assertDontSee(__('home.areas'))
            ->assertDontSeeHtml('href="'.route('area.show', 'aaa-wijk').'"');
    }

    public function test_home_page_areas_are_sorted_alphabetically(): void
    {
        Area::query()->create(['name' => 'Zzz-wijk', 'slug' => 'zzz-wijk']);
        Area::query()->create(['name' => 'Aaa-wijk', 'slug' => 'aaa-wijk']);

        $areaNames = Livewire::test('pages::index')->instance()->areas()->pluck('name')->all();

        $this->assertSame(['Aaa-wijk', 'Zzz-wijk'], $areaNames);
    }

    public function test_home_page_areas_count_only_visible_topics(): void
    {
        $area = Area::query()->create(['name' => 'Telwijk', 'slug' => 'telwijk']);
        $forum = $this->createForumWithTopics(4);
        $topics = $forum->topics()->get();

        $area->topics()->attach($topics->take(3)->pluck('id'));
        $topics->first()->update(['is_visible' => false]);

        $areas = Livewire::test('pages::index')->instance()->areas();

        $this->assertSame(2, $areas->firstWhere('id', $area->id)->topics_count);
    }

    public function test_home_page_shows_five_newest_members(): void
    {
        $oldest = User::factory()->create(['created_at' => now()->subDays(10)]);
        $newest = User::factory()->count(5)->sequence(
            fn ($sequence) => ['created_at' => now()->subDays($sequence->index)]
        )->create();

        $memberIds = Livewire::test('pages::index')->instance()->newestMembers()->pluck('id')->all();

        $this->assertSame($newest->pluck('id')->all(), $memberIds);

        Livewire::test('pages::index')
            ->assertSeeInOrder([$newest->first()->username, $newest->first()->created_at->translatedFormat('j M Y')]);
        $this->assertNotContains($oldest->id, $memberIds);
    }

    public function test_home_page_shows_five_upcoming_published_activities_in_order(): void
    {
        $past = Activity::factory()->create(['starts_on' => today()->subDays(3), 'ends_on' => today()->subDays(3)]);
        $unpublished = Activity::factory()->create(['starts_on' => today(), 'ends_on' => today(), 'published_at' => null]);
        $upcoming = collect(range(6, 1))->map(
            fn (int $days) => Activity::factory()->create(['starts_on' => today()->addDays($days), 'ends_on' => today()->addDays($days)])
        );

        $activityIds = Livewire::test('pages::index')
            ->assertSee($upcoming->last()->title)
            ->instance()
            ->upcomingActivities()
            ->pluck('id')
            ->all();

        $this->assertSame($upcoming->reverse()->take(5)->pluck('id')->values()->all(), $activityIds);
        $this->assertNotContains($past->id, $activityIds);
        $this->assertNotContains($unpublished->id, $activityIds);
    }

    public function test_home_page_hides_remaining_topic_count_when_all_topics_are_shown(): void
    {
        $this->createForumWithTopics(3);

        Livewire::test('pages::index')
            ->assertDontSeeHtml('forumListItem__moreTopics');
    }
}
