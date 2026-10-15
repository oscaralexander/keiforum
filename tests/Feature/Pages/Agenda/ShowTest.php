<?php

namespace Tests\Feature\Pages\Agenda;

use App\Enums\AttendanceStatus;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Livewire\Livewire;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_page_is_reachable_by_user_date_and_slug(): void
    {
        $activity = Activity::factory()->create([
            'title' => 'Jazz op de Hof',
            'starts_on' => '2099-10-14',
            'ends_on' => '2099-10-14',
            'price' => 1250,
        ]);

        $this->assertSame(url('/agenda/@'.$activity->user->username.'/2099-10-14/jazz-op-de-hof'), $activity->url());

        $this->get($activity->url())
            ->assertOk()
            ->assertSee('Jazz op de Hof')
            ->assertSee(Number::currency(12.5, in: 'EUR', locale: 'nl'))
            ->assertSee($activity->location_name)
            ->assertSee('"@type": "Event"', false);
    }

    public function test_free_activity_shows_free(): void
    {
        $activity = Activity::factory()->create(['price' => 0]);

        $this->get($activity->url())->assertSee(__('agenda/show.free'));
    }

    public function test_wrong_date_or_user_returns_404(): void
    {
        $activity = Activity::factory()->create();
        $otherUser = User::factory()->create();

        $this->get(route('agenda.show', [$activity->user, '2000-01-01', $activity->slug]))->assertNotFound();
        $this->get(route('agenda.show', [$otherUser, $activity->starts_on->format('Y-m-d'), $activity->slug]))->assertNotFound();
    }

    public function test_unpublished_activity_is_only_visible_to_owner(): void
    {
        $activity = Activity::factory()->unpublished()->create();

        $this->get($activity->url())->assertNotFound();
        $this->actingAs(User::factory()->create())->get($activity->url())->assertNotFound();
        $this->actingAs($activity->user)->get($activity->url())->assertOk()->assertSee(__('agenda/show.unpublished'));
    }

    public function test_slug_is_unique_per_user_and_day(): void
    {
        $user = User::factory()->create();
        $first = Activity::factory()->for($user)->create(['title' => 'Filmavond', 'starts_on' => '2099-01-01', 'ends_on' => '2099-01-01']);
        $second = Activity::factory()->for($user)->create(['title' => 'Filmavond', 'starts_on' => '2099-01-01', 'ends_on' => '2099-01-01']);
        $otherDay = Activity::factory()->for($user)->create(['title' => 'Filmavond', 'starts_on' => '2099-01-02', 'ends_on' => '2099-01-02']);

        $this->assertSame(['filmavond', 'filmavond-2', 'filmavond'], [$first->slug, $second->slug, $otherDay->slug]);
    }

    public function test_description_is_sanitized(): void
    {
        $activity = Activity::factory()->create(['description' => '<p>Welkom</p><script>alert(1)</script>']);

        $this->assertSame('<p>Welkom</p>', $activity->description);
    }

    public function test_member_can_say_they_are_going_and_toggle_it_off(): void
    {
        $activity = Activity::factory()->create();
        $member = User::factory()->create();

        $component = Livewire::actingAs($member)
            ->test('pages::agenda.show', ['user' => $activity->user, 'date' => $activity->starts_on->format('Y-m-d'), 'slug' => $activity->slug])
            ->call('attend', AttendanceStatus::GOING->value)
            ->assertSet('attendanceStatus', AttendanceStatus::GOING)
            ->assertSee(trans_choice('agenda/show.going_count', 1, ['count' => 1]));

        $this->assertSame('going', $activity->attendees()->first()->pivot->status);

        $component->call('attend', AttendanceStatus::GOING->value)
            ->assertSet('attendanceStatus', null);

        $this->assertSame(0, $activity->attendees()->count());
    }

    public function test_member_can_switch_from_going_to_maybe(): void
    {
        $activity = Activity::factory()->create();
        $member = User::factory()->create();

        Livewire::actingAs($member)
            ->test('pages::agenda.show', ['user' => $activity->user, 'date' => $activity->starts_on->format('Y-m-d'), 'slug' => $activity->slug])
            ->call('attend', AttendanceStatus::GOING->value)
            ->call('attend', AttendanceStatus::MAYBE->value)
            ->assertSet('attendanceStatus', AttendanceStatus::MAYBE);

        $this->assertSame(1, $activity->attendees()->count());
        $this->assertSame(1, $activity->maybeUsers()->count());
    }

    public function test_cannot_attend_past_activity(): void
    {
        $activity = Activity::factory()->past()->create();

        Livewire::actingAs(User::factory()->create())
            ->test('pages::agenda.show', ['user' => $activity->user, 'date' => $activity->starts_on->format('Y-m-d'), 'slug' => $activity->slug])
            ->call('attend', AttendanceStatus::GOING->value)
            ->assertForbidden();
    }

    public function test_guest_sees_login_links_instead_of_attendance_buttons(): void
    {
        $activity = Activity::factory()->create();

        $this->get($activity->url())
            ->assertSee(route('login', ['redirect' => parse_url($activity->url(), PHP_URL_PATH)]), false)
            ->assertDontSee('wire:click="attend', false);
    }

    public function test_invalid_attendance_status_is_rejected(): void
    {
        $activity = Activity::factory()->create();

        $this->expectException(\ValueError::class);

        Livewire::actingAs(User::factory()->create())
            ->test('pages::agenda.show', ['user' => $activity->user, 'date' => $activity->starts_on->format('Y-m-d'), 'slug' => $activity->slug])
            ->call('attend', 'nope');
    }
}
