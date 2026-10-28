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

    public function test_page_title_includes_the_start_date(): void
    {
        $activity = Activity::factory()->create([
            'title' => 'Spätkauf',
            'starts_on' => '2099-10-24',
            'ends_on' => '2099-10-24',
        ]);

        $this->get($activity->url())
            ->assertSee('Spätkauf - 24 oktober 2099 - '.config('app.name'));
    }

    public function test_header_shows_the_start_date_without_time_before_the_organizer(): void
    {
        $organizer = User::factory()->create(['name' => 'Podium FLUOR']);
        $activity = Activity::factory()->for($organizer)->create([
            'location_name' => 'Elders',
            'starts_on' => '2099-10-24',
            'ends_on' => '2099-10-25',
            'starts_at' => '23:00',
            'ends_at' => '05:00',
        ]);

        $this->get($activity->url())
            ->assertSeeInOrder(['class="headerMeta"', '24 okt. 2099', 'Podium FLUOR', 'class="activity"'], false)
            ->assertDontSee('24 okt. 2099, 23:00');
    }

    public function test_details_list_the_location_first(): void
    {
        $activity = Activity::factory()->create(['location_name' => 'Podium FLUOR']);

        $this->get($activity->url())
            ->assertSeeInOrder(['class="activity__details"', '#map-pin', 'Podium FLUOR', '#calendar', '#clock'], false)
            ->assertSee('aria-label="Locatie" title="Locatie"', false)
            ->assertDontSee('@lang', false);
    }

    public function test_organizer_is_shown_by_name(): void
    {
        $organizer = User::factory()->create(['name' => "Anna's Bookcafé", 'username' => 'annasbookcafe']);
        $activity = Activity::factory()->for($organizer)->create();

        $this->get($activity->url())
            ->assertSeeInOrder(['class="headerMeta"', route('agenda.user', $organizer), 'Anna&#039;s Bookcafé</a>'], false);
    }

    public function test_organizer_falls_back_to_username_without_name(): void
    {
        $organizer = User::factory()->create(['name' => '', 'username' => 'podiumfluor']);
        $activity = Activity::factory()->for($organizer)->create();

        $this->get($activity->url())
            ->assertSeeInOrder(['class="headerMeta"', 'podiumfluor</a>'], false);
    }

    public function test_more_from_organizer_lists_up_to_four_other_upcoming_activities(): void
    {
        $organizer = User::factory()->create(['name' => 'Podium FLUOR']);
        $activity = Activity::factory()->for($organizer)->create(['title' => 'Huidige avond']);
        $upcoming = collect(range(1, 5))->map(fn (int $day) => Activity::factory()->for($organizer)->create([
            'title' => 'Avond '.$day,
            'starts_on' => now()->addWeeks(2)->addDays($day),
            'ends_on' => now()->addWeeks(2)->addDays($day),
        ]));
        Activity::factory()->for($organizer)->past()->create(['title' => 'Voorbije avond']);
        Activity::factory()->for($organizer)->unpublished()->create(['title' => 'Geheime avond']);
        Activity::factory()->create(['title' => 'Andermans avond']);

        $response = $this->get($activity->url())
            ->assertSee('Meer van Podium FLUOR')
            ->assertSeeInOrder(['class="panel__body activityMore"', route('agenda.user', $organizer), 'Bekijk alles'], false);

        Livewire::test('pages::agenda.show', [
            'user' => $organizer,
            'date' => $activity->starts_on->format('Y-m-d'),
            'slug' => $activity->slug,
        ])->assertSet('moreActivities', fn ($activities): bool => $activities->pluck('id')->all() === $upcoming->take(4)->pluck('id')->all());

        $response->assertSeeInOrder(['Avond 1', 'Avond 2', 'Avond 3', 'Avond 4'])
            ->assertSee($upcoming->first()->starts_on->translatedFormat('j M'))
            ->assertDontSee('<li class="meta__item">20:00</li>', false)
            ->assertDontSee('Avond 5')
            ->assertDontSee('Voorbije avond')
            ->assertDontSee('Geheime avond')
            ->assertDontSee('Andermans avond');
    }

    public function test_more_from_organizer_is_hidden_without_other_upcoming_activities(): void
    {
        $activity = Activity::factory()->create();

        $this->get($activity->url())->assertDontSee('class="panel__body activityMore"', false);
    }

    public function test_night_activity_only_shows_the_start_date(): void
    {
        $activity = Activity::factory()->create([
            'starts_on' => '2099-10-24',
            'ends_on' => '2099-10-25',
            'starts_at' => '23:00',
            'ends_at' => '05:00',
        ]);

        $this->get($activity->url())
            ->assertSee('Zaterdag, 24 oktober 2099')
            ->assertDontSee('25 oktober');
    }

    public function test_multi_day_activity_shows_both_dates(): void
    {
        $activity = Activity::factory()->create([
            'starts_on' => '2099-10-24',
            'ends_on' => '2099-10-25',
            'starts_at' => '20:00',
            'ends_at' => '18:00',
        ]);

        $this->get($activity->url())
            ->assertSee('Zaterdag, 24 oktober – zondag, 25 oktober 2099');
    }

    public function test_owner_sees_the_edit_button(): void
    {
        $activity = Activity::factory()->create();

        $this->actingAs($activity->user)
            ->get($activity->url())
            ->assertSee($activity->editUrl());
    }

    public function test_admins_see_the_edit_button(): void
    {
        $activity = Activity::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get($activity->url())
            ->assertSee($activity->editUrl());
    }

    public function test_other_users_and_guests_do_not_see_the_edit_button(): void
    {
        $activity = Activity::factory()->create();

        $this->get($activity->url())->assertDontSee($activity->editUrl());

        $this->actingAs(User::factory()->create())
            ->get($activity->url())
            ->assertDontSee($activity->editUrl());
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
            ->assertSet('attendanceStatus', AttendanceStatus::GOING);

        $this->assertSame('going', $activity->attendees()->first()->pivot->status);

        $this->actingAs($member)
            ->get($activity->url())
            ->assertSee(trans_choice('agenda/show.going_count', 1, ['count' => 1]));

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
