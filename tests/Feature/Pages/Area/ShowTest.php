<?php

namespace Tests\Feature\Pages\Area;

use App\Models\Area;
use App\Models\Forum;
use App\Models\Topic;
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
}
