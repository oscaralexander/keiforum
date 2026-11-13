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

    public function test_pagination_links_to_the_first_previous_next_and_last_page(): void
    {
        $forum = Forum::factory()->create(['id' => 999]);
        Topic::factory()->for($forum)->count(Topic::PAGINATE_COUNT * 2 + 1)->create()
            ->each(fn (Topic $topic) => Post::factory()->for($topic)->create());

        $this->get(route('forum.show', $forum).'?p=2')
            ->assertOk()
            ->assertSeeInOrder([
                'aria-label="'.__('pagination.first').'"', 'p=1', '#arrow-left-to-line',
                'aria-label="'.__('pagination.previous').'"', 'p=1', '#arrow-left',
                'Pagina 2 van 3',
                'aria-label="'.__('pagination.next').'"', 'p=3', '#arrow-right',
                'aria-label="'.__('pagination.last').'"', 'p=3', '#arrow-right-to-line',
            ], false)
            ->assertDontSee('resultaten');
    }

    public function test_pagination_disables_the_buttons_at_the_ends(): void
    {
        $forum = Forum::factory()->create(['id' => 999]);
        Topic::factory()->for($forum)->count(Topic::PAGINATE_COUNT + 1)->create()
            ->each(fn (Topic $topic) => Post::factory()->for($topic)->create());

        $this->get(route('forum.show', $forum))
            ->assertSee('Pagina 1 van 2')
            ->assertDontSee('aria-label="'.__('pagination.first').'"', false)
            ->assertDontSee('aria-label="'.__('pagination.previous').'"', false)
            ->assertSee('aria-label="'.__('pagination.last').'"', false);

        $this->get(route('forum.show', $forum).'?p=2')
            ->assertSee('Pagina 2 van 2')
            ->assertSee('aria-label="'.__('pagination.first').'"', false)
            ->assertDontSee('aria-label="'.__('pagination.next').'"', false)
            ->assertDontSee('aria-label="'.__('pagination.last').'"', false);
    }

    public function test_header_has_no_path(): void
    {
        $forum = Forum::factory()->create(['id' => 999]);

        $this->get(route('forum.show', $forum))
            ->assertOk()
            ->assertDontSee('class="path"', false);
    }
}
