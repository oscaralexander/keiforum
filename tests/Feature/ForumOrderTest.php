<?php

namespace Tests\Feature;

use App\Models\Forum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ForumOrderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Forum, 1: Forum, 2: Forum}
     */
    private function createForums(): array
    {
        $first = Forum::factory()->create(['name' => 'Eerste forum', 'position' => 1]);
        $third = Forum::factory()->create(['name' => 'Derde forum', 'position' => 3]);
        $second = Forum::factory()->create(['name' => 'Tweede forum', 'position' => 2]);

        Forum::query()->where('slug', config('news.forum_slug'))->update(['position' => 4]);

        return [$first, $second, $third];
    }

    public function test_home_page_lists_forums_by_position(): void
    {
        $this->createForums();

        Livewire::test('pages::index')
            ->assertSeeInOrder(['Eerste forum', 'Tweede forum', 'Derde forum']);
    }

    public function test_create_topic_page_lists_forums_by_position(): void
    {
        [$first, $second, $third] = $this->createForums();

        $forumIds = Livewire::actingAs(User::factory()->create())
            ->test('pages::topic.create', ['forum' => $first])
            ->instance()
            ->forums()
            ->pluck('id')
            ->take(3)
            ->all();

        $this->assertSame([$first->id, $second->id, $third->id], $forumIds);
    }

    public function test_sitemap_lists_forums_by_position(): void
    {
        [$first, $second, $third] = $this->createForums();

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSeeInOrder([
                route('forum.show', $first),
                route('forum.show', $second),
                route('forum.show', $third),
            ], false);
    }
}
