<?php

namespace Tests\Feature\Pages\Topic;

use App\Models\Forum;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use RefreshDatabase;

    protected function newsForum(): Forum
    {
        return Forum::query()->where('slug', config('news.forum_slug'))->firstOrFail();
    }

    public function test_news_forum_is_locked(): void
    {
        $this->assertTrue($this->newsForum()->is_locked);
    }

    public function test_only_admins_may_start_topics_in_locked_forums(): void
    {
        $lockedForum = Forum::factory()->create(['is_locked' => true]);
        $openForum = Forum::factory()->create();

        $this->assertTrue(User::factory()->admin()->create()->can('createTopic', $lockedForum));
        $this->assertFalse(User::factory()->create()->can('createTopic', $lockedForum));
        $this->assertTrue(User::factory()->create()->can('createTopic', $openForum));
    }

    public function test_member_cannot_open_create_page_for_locked_forum(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('topic.create', $this->newsForum()))
            ->assertForbidden();
    }

    public function test_admin_can_open_create_page_for_locked_forum(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('topic.create', $this->newsForum()))
            ->assertOk();
    }

    public function test_locked_forum_is_not_offered_to_members(): void
    {
        $openForum = Forum::factory()->create();

        Livewire::actingAs(User::factory()->create())
            ->test('pages::topic.create', ['forum' => null])
            ->assertSee($openForum->name)
            ->assertDontSee($this->newsForum()->name);
    }

    public function test_member_cannot_submit_topic_to_locked_forum(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test('pages::topic.create', ['forum' => null])
            ->set('forum_id', $this->newsForum()->id)
            ->set('title', 'Mijn nieuws')
            ->set('body', '<p>Hallo</p>')
            ->call('submit')
            ->assertHasErrors(['forum_id' => 'in']);

        $this->assertDatabaseMissing(Topic::class, ['title' => 'Mijn nieuws']);
    }

    public function test_member_cannot_submit_topic_to_unknown_forum(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test('pages::topic.create', ['forum' => null])
            ->set('forum_id', 999)
            ->set('title', 'Nergens')
            ->set('body', '<p>Hallo</p>')
            ->call('submit')
            ->assertHasErrors(['forum_id' => 'in']);
    }

    public function test_admin_can_submit_topic_to_locked_forum(): void
    {
        $newsForum = $this->newsForum();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test('pages::topic.create', ['forum' => $newsForum])
            ->set('title', 'Belangrijk nieuws')
            ->set('body', '<p>Hallo</p>')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(Topic::class, ['forum_id' => $newsForum->id, 'title' => 'Belangrijk nieuws']);
    }

    public function test_new_topic_button_is_hidden_in_locked_forum_for_members_and_guests(): void
    {
        $newsForum = $this->newsForum();
        $createUrl = route('topic.create', $newsForum);

        $this->get(route('forum.show', $newsForum))->assertOk()->assertDontSee($createUrl, false);
        $this->actingAs(User::factory()->create())->get(route('forum.show', $newsForum))->assertOk()->assertDontSee($createUrl, false);
        $this->actingAs(User::factory()->admin()->create())->get(route('forum.show', $newsForum))->assertOk()->assertSee($createUrl, false);
    }

    public function test_new_topic_button_is_shown_in_open_forum_for_guests(): void
    {
        $openForum = Forum::factory()->create();

        $this->get(route('forum.show', $openForum))
            ->assertOk()
            ->assertSee(route('topic.create', $openForum), false);
    }
}
