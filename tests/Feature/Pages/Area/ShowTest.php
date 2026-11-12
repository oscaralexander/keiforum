<?php

namespace Tests\Feature\Pages\Area;

use App\Models\Area;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    private function createArea(string $name = 'Test Area'): Area
    {
        return Area::create(['name' => $name, 'slug' => Str::slug($name)]);
    }

    public function test_area_show_page_renders(): void
    {
        $area = $this->createArea();

        $this->get(route('area.show', $area))->assertOk();
    }

    public function test_area_show_displays_topics_in_area(): void
    {
        $area = $this->createArea();
        $topic = Topic::factory()->create();
        $topic->areas()->attach($area);

        Livewire::test('pages::area.show', ['area' => $area])
            ->assertSee($topic->title);
    }

    public function test_area_show_does_not_display_topics_outside_area(): void
    {
        $area = $this->createArea();
        $otherTopic = Topic::factory()->create();

        Livewire::test('pages::area.show', ['area' => $area])
            ->assertDontSee($otherTopic->title);
    }

    public function test_area_show_all_forums_selected_by_default(): void
    {
        $area = $this->createArea();
        $forum1 = Forum::factory()->create();
        $forum2 = Forum::factory()->create();

        $topic1 = Topic::factory()->create(['forum_id' => $forum1->id]);
        $topic2 = Topic::factory()->create(['forum_id' => $forum2->id]);
        $topic1->areas()->attach($area);
        $topic2->areas()->attach($area);

        Livewire::test('pages::area.show', ['area' => $area])
            ->assertSee($topic1->title)
            ->assertSee($topic2->title);
    }

    public function test_area_show_filters_topics_by_selected_forums(): void
    {
        $area = $this->createArea();
        $forum1 = Forum::factory()->create();
        $forum2 = Forum::factory()->create();

        $topic1 = Topic::factory()->create(['forum_id' => $forum1->id]);
        $topic2 = Topic::factory()->create(['forum_id' => $forum2->id]);
        $topic1->areas()->attach($area);
        $topic2->areas()->attach($area);

        Livewire::test('pages::area.show', ['area' => $area])
            ->set('selectedForumIds', [$forum1->id])
            ->assertSee($topic1->title)
            ->assertDontSee($topic2->title);
    }

    public function test_area_show_displays_no_topics_message_when_empty(): void
    {
        $area = $this->createArea();

        Livewire::test('pages::area.show', ['area' => $area])
            ->assertSee(__('area/show.no_topics'));
    }

    public function test_sidebar_lists_most_active_members_from_the_area(): void
    {
        $area = $this->createArea('Stadskern');
        $otherArea = $this->createArea('Vathorst');
        $quiet = User::factory()->create(['area_id' => $area->id, 'username' => 'quiet']);
        $chatty = User::factory()->create(['area_id' => $area->id, 'username' => 'chatty']);
        $outsider = User::factory()->create(['area_id' => $otherArea->id, 'username' => 'outsider']);
        Post::factory()->count(3)->for($chatty)->create();
        Post::factory()->for($quiet)->create();
        Post::factory()->count(5)->for($outsider)->create();

        $memberIds = Livewire::test('pages::area.show', ['area' => $area])
            ->assertSee('Actiefste leden uit Stadskern')
            ->instance()
            ->mostActiveMembers()
            ->pluck('id')
            ->all();

        $this->assertSame([$chatty->id, $quiet->id], $memberIds);
    }

    public function test_sidebar_shows_at_most_five_members(): void
    {
        $area = $this->createArea();
        User::factory()->count(6)->create(['area_id' => $area->id])
            ->each(fn (User $member) => Post::factory()->for($member)->create());

        $this->assertCount(5, Livewire::test('pages::area.show', ['area' => $area])->instance()->mostActiveMembers());
    }

    public function test_sidebar_shows_message_without_members_from_the_area(): void
    {
        $area = $this->createArea('Stadskern');
        User::factory()->create(['area_id' => $area->id]);

        Livewire::test('pages::area.show', ['area' => $area])
            ->assertSee('Helaas, nog geen leden uit Stadskern');
    }

    public function test_forum_filters_are_inside_the_topics_panel(): void
    {
        $area = $this->createArea();

        $this->get(route('area.show', $area))
            ->assertOk()
            ->assertSeeInOrder(['<div class="panel">', '<div class="panel__body">', 'wire:model.live="selectedForumIds"'], false);
    }
}
