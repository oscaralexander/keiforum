<?php

namespace Tests\Feature\Pages\Forum;

use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_filters_are_inside_the_topics_panel(): void
    {
        $marketplace = Forum::query()->find(2) ?? Forum::factory()->create(['id' => 2]);
        $topic = Topic::factory()->for($marketplace)->create();
        Post::factory()->for($topic)->create();

        $this->get(route('forum.show', $marketplace))
            ->assertOk()
            ->assertSeeInOrder([
                '<div class="panel">',
                '<div class="panel__body">',
                'wire:model.live="adTypes"',
                '<ul class="topicList">',
                e($topic->title),
            ], false);
    }

    public function test_other_forums_have_no_filters(): void
    {
        $forum = Forum::factory()->create(['id' => 999]);

        $this->get(route('forum.show', $forum))
            ->assertOk()
            ->assertDontSee('wire:model.live="adTypes"', false)
            ->assertSee(__('forum/show.no_topics'));
    }

    public function test_pagination_is_inside_the_topics_panel(): void
    {
        $forum = Forum::factory()->create(['id' => 999]);
        Topic::factory()->for($forum)->count(Topic::PAGINATE_COUNT + 1)->create()
            ->each(fn (Topic $topic) => Post::factory()->for($topic)->create());

        $this->get(route('forum.show', $forum))
            ->assertOk()
            ->assertSeeInOrder([
                '<div class="panel">',
                '<ul class="topicList">',
                '</ul>',
                '<div class="panel__body">',
                '<nav class="pagination"',
                'class="sidebarLayout__side"',
            ], false);
    }

    public function test_header_has_no_path(): void
    {
        $forum = Forum::factory()->create(['id' => 999]);

        $this->get(route('forum.show', $forum))
            ->assertOk()
            ->assertDontSee('class="path"', false);
    }
}
