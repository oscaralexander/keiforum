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
            ->assertSeeInOrder([__('members/index.total_members'), '3']);
    }

    public function test_new_members_count_shows_users_created_in_last_7_days(): void
    {
        User::factory()->create(['created_at' => now()->subDays(3)]);
        User::factory()->create(['created_at' => now()->subDays(10)]);

        Livewire::test('pages::members.index')
            ->assertSeeInOrder([__('members/index.new_members'), '1']);
    }

    public function test_new_members_count_excludes_users_older_than_7_days(): void
    {
        User::factory()->create(['created_at' => now()->subDays(8)]);

        Livewire::test('pages::members.index')
            ->assertSeeInOrder([__('members/index.new_members'), '0']);
    }

    public function test_latest_member_username_is_displayed(): void
    {
        User::factory()->create(['created_at' => now()->subDays(2)]);
        $latest = User::factory()->create(['created_at' => now()->subDay()]);

        Livewire::test('pages::members.index')
            ->assertSeeInOrder([__('members/index.latest_member'), $latest->username]);
    }

    public function test_latest_member_shows_dash_when_no_members(): void
    {
        Livewire::test('pages::members.index')
            ->assertSee('—');
    }
}
