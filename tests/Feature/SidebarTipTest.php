<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SidebarTipTest extends TestCase
{
    use RefreshDatabase;

    private function introductionTopic(): Topic
    {
        $topic = Topic::factory()->create();
        config(['tips.introduce.topic_id' => $topic->id]);

        return $topic;
    }

    public function test_tip_links_to_its_topic(): void
    {
        $topic = $this->introductionTopic();

        Livewire::actingAs(User::factory()->create())
            ->test('community-sidebar', ['tip' => 'introduce'])
            ->assertSee(__('tips.introduce.title'))
            ->assertSeeHtml('href="'.e($topic->url()).'"');
    }

    public function test_tip_is_hidden_without_a_tip_key(): void
    {
        $this->introductionTopic();

        Livewire::actingAs(User::factory()->create())
            ->test('community-sidebar')
            ->assertDontSee(__('tips.introduce.title'));
    }

    public function test_tip_is_hidden_when_its_topic_does_not_exist(): void
    {
        config(['tips.introduce.topic_id' => 999]);

        Livewire::actingAs(User::factory()->create())
            ->test('community-sidebar', ['tip' => 'introduce'])
            ->assertDontSee(__('tips.introduce.title'));
    }

    public function test_tip_is_hidden_for_members_who_already_posted_in_the_topic(): void
    {
        $topic = $this->introductionTopic();
        $member = User::factory()->create();
        Post::factory()->for($topic)->for($member)->create();

        Livewire::actingAs($member)
            ->test('community-sidebar', ['tip' => 'introduce'])
            ->assertDontSee(__('tips.introduce.title'));
    }

    public function test_tip_is_shown_to_members_who_have_not_posted_in_the_topic(): void
    {
        $this->introductionTopic();

        Livewire::actingAs(User::factory()->create())
            ->test('community-sidebar', ['tip' => 'introduce'])
            ->assertSee(__('tips.introduce.title'));
    }

    public function test_home_and_forum_pages_show_no_tips_for_now(): void
    {
        $topic = $this->introductionTopic();
        Post::factory()->for($topic)->create();
        $this->actingAs(User::factory()->create());

        $this->get(route('home'))->assertOk()->assertDontSee(__('tips.introduce.title'));
        $this->get(route('forum.show', $topic->forum))->assertOk()->assertDontSee(__('tips.introduce.title'));
    }

    public function test_home_page_shows_no_register_invitation_for_now(): void
    {
        $this->get(route('home'))->assertOk()->assertDontSee(__('tips.register.title'));
    }

    public function test_guests_are_invited_to_register_instead(): void
    {
        $this->introductionTopic();

        Livewire::test('community-sidebar', ['tip' => 'introduce'])
            ->assertSee(__('tips.register.title'))
            ->assertSeeHtml('href="'.e(route('register')).'"')
            ->assertDontSee(__('tips.introduce.title'));
    }

    public function test_guests_are_not_invited_to_register_on_pages_without_a_tip(): void
    {
        Livewire::test('community-sidebar')
            ->assertDontSee(__('tips.register.title'));
    }

    public function test_members_are_not_invited_to_register(): void
    {
        $this->introductionTopic();

        Livewire::actingAs(User::factory()->create())
            ->test('community-sidebar', ['tip' => 'introduce'])
            ->assertDontSee(__('tips.register.title'));
    }
}
