<?php

namespace Tests\Feature\Pages\Members;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_index_page_renders(): void
    {
        $this->get(route('members'))->assertOk();
    }

    public function test_total_members_count_is_displayed(): void
    {
        User::factory()->count(3)->create();

        Livewire::test('pages::members.index')
            ->assertSeeInOrder([__('members/index.stats.members_count'), '3']);
    }

    public function test_new_members_count_shows_users_created_in_last_7_days(): void
    {
        User::factory()->create(['created_at' => now()->subDays(3)]);
        User::factory()->create(['created_at' => now()->subDays(10)]);

        Livewire::test('pages::members.index')
            ->assertSeeInOrder([__('members/index.stats.members_count_week'), '1']);
    }

    public function test_new_members_count_excludes_users_older_than_7_days(): void
    {
        User::factory()->create(['created_at' => now()->subDays(8)]);

        Livewire::test('pages::members.index')
            ->assertSeeInOrder([__('members/index.stats.members_count_week'), '0']);
    }

    public function test_latest_member_username_is_displayed(): void
    {
        User::factory()->create(['created_at' => now()->subDays(2)]);
        $latest = User::factory()->create(['created_at' => now()->subDay()]);

        Livewire::test('pages::members.index')
            ->assertSeeInOrder([__('members/index.stats.latest_member'), $latest->username]);
    }

    public function test_latest_member_shows_dash_when_no_members(): void
    {
        Livewire::test('pages::members.index')
            ->assertSee('—');
    }

    public function test_unverified_users_are_not_listed_or_counted(): void
    {
        $verified = User::factory()->create(['created_at' => now()->subDays(2)]);
        $unverified = User::factory()->unverified()->create(['created_at' => now()->subDay()]);

        Livewire::test('pages::members.index')
            ->assertSet('totalMembers', 1)
            ->assertSet('newMembersCount', 1)
            ->assertSet('latestMember.id', $verified->id)
            ->assertSee($verified->username)
            ->assertDontSee($unverified->username);
    }

    public function test_active_scope_only_includes_verified_users(): void
    {
        $verified = User::factory()->create();
        User::factory()->unverified()->create();

        $this->assertSame([$verified->id], User::query()->active()->members()->pluck('id')->all());
    }

    public function test_news_user_is_not_listed_as_member(): void
    {
        Livewire::test('pages::members.index')
            ->assertSet('totalMembers', 0)
            ->assertDontSee(config('news.username'));
    }
}
