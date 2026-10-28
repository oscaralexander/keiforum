<?php

namespace Tests\Feature\Pages\Agenda;

use App\Enums\ActivityCategory;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_agenda_lists_upcoming_published_activities_in_chronological_order(): void
    {
        $later = Activity::factory()->create(['starts_on' => today()->addDays(5), 'ends_on' => today()->addDays(5)]);
        $sooner = Activity::factory()->create(['starts_on' => today()->addDay(), 'ends_on' => today()->addDay()]);
        $past = Activity::factory()->past()->create();
        $unpublished = Activity::factory()->unpublished()->create();

        $this->get(route('agenda'))
            ->assertOk()
            ->assertSeeInOrder([$sooner->title, $later->title])
            ->assertDontSee($past->title)
            ->assertDontSee($unpublished->title);
    }

    public function test_activity_that_started_earlier_and_is_still_running_is_listed_under_today(): void
    {
        $running = Activity::factory()->create(['starts_on' => today()->subDays(2), 'ends_on' => today()->addDay()]);

        Livewire::test('pages::agenda.index')
            ->assertSet('activitiesByDay', fn ($days) => $days->keys()->all() === [today()->format('Y-m-d')])
            ->assertSeeInOrder([__('agenda/index.today'), $running->title]);
    }

    public function test_category_page_only_lists_that_category(): void
    {
        $club = Activity::factory()->create(['category' => ActivityCategory::CLUB]);
        $dance = Activity::factory()->create(['category' => ActivityCategory::DANCE]);

        $this->get('/agenda/club')
            ->assertOk()
            ->assertSee($club->title)
            ->assertDontSee($dance->title);
    }

    public function test_user_page_only_lists_activities_of_that_user(): void
    {
        $venue = User::factory()->create();
        $own = Activity::factory()->for($venue)->create();
        $other = Activity::factory()->create();

        $this->get('/agenda/@'.$venue->username)
            ->assertOk()
            ->assertSee(__('agenda/index.title_user', ['username' => $venue->username]))
            ->assertSee($own->title)
            ->assertDontSee($other->title);
    }

    public function test_user_category_page_combines_both_filters(): void
    {
        $venue = User::factory()->create();
        $ownClub = Activity::factory()->for($venue)->create(['category' => ActivityCategory::CLUB]);
        $ownDance = Activity::factory()->for($venue)->create(['category' => ActivityCategory::DANCE]);
        $otherClub = Activity::factory()->create(['category' => ActivityCategory::CLUB]);

        $this->get('/agenda/@'.$venue->username.'/club')
            ->assertOk()
            ->assertSee($ownClub->title)
            ->assertDontSee($ownDance->title)
            ->assertDontSee($otherClub->title);
    }

    public function test_category_filters_keep_the_user(): void
    {
        $venue = User::factory()->create();

        $this->get('/agenda/@'.$venue->username)
            ->assertSee('href="'.route('agenda.user.category', [$venue, 'muziek']).'"', false);
    }

    public function test_unknown_category_and_user_return_404(): void
    {
        $this->get('/agenda/onbekend')->assertNotFound();
        $this->get('/agenda/@bestaatniet')->assertNotFound();
    }

    public function test_create_button_is_only_shown_to_organizers(): void
    {
        $this->get(route('agenda'))->assertDontSee(route('agenda.create'));

        $this->actingAs(User::factory()->create())
            ->get(route('agenda'))
            ->assertDontSee(route('agenda.create'));

        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('agenda'))
            ->assertSee(route('agenda.create'))
            ->assertSeeInOrder(['class="header__actions"', route('agenda.create'), 'Nieuwe activiteit'], false);
    }

    public function test_empty_agenda_shows_message(): void
    {
        $this->get(route('agenda'))->assertSee(__('agenda/index.empty'));
    }

    public function test_activity_card_shows_category_title_location_and_start_time(): void
    {
        $activity = Activity::factory()->create([
            'category' => ActivityCategory::CLUB,
            'title' => 'Spätkauf',
            'starts_on' => today()->addDay(),
            'ends_on' => today()->addDay(),
            'starts_at' => '23:00',
            'location_name' => 'Podium FLUOR',
        ]);

        $this->get(route('agenda'))
            ->assertSeeInOrder([
                ActivityCategory::CLUB->label(),
                'Spätkauf',
                'Podium FLUOR',
                '23:00',
            ]);
    }

    public function test_activity_card_links_to_its_category(): void
    {
        Activity::factory()->create(['category' => ActivityCategory::CLUB]);

        $this->get(route('agenda'))
            ->assertSee('class="activityListItem__category" href="'.route('agenda.category', 'club').'"', false);
    }

    public function test_card_without_a_poster_shows_a_placeholder(): void
    {
        Activity::factory()->create(['poster_path' => null]);

        $this->get(route('agenda'))->assertSee('activityPoster activityPoster--placeholder', false);
    }

    public function test_card_with_a_poster_has_no_placeholder(): void
    {
        Activity::factory()->create(['poster_path' => 'posters/poster.webp']);

        $this->get(route('agenda'))->assertDontSee('activityPoster--placeholder', false);
    }

    public function test_categories_are_listed_alphabetically_with_other_last(): void
    {
        $this->assertSame(
            ['Club', 'Dans', 'Kunst', 'Muziek', 'Theater', 'Anders'],
            ActivityCategory::options()->values()->all(),
        );
    }

    public function test_relative_day_headers_show_the_date(): void
    {
        Activity::factory()->create(['starts_on' => today(), 'ends_on' => today()]);
        Activity::factory()->create(['starts_on' => today()->addDay(), 'ends_on' => today()->addDay()]);

        $this->get(route('agenda'))
            ->assertSeeInOrder([
                __('agenda/index.today'),
                today()->translatedFormat('j F'),
                __('agenda/index.tomorrow'),
                today()->addDay()->translatedFormat('j F'),
            ]);
    }

    public function test_other_day_headers_show_the_weekday_and_the_date_without_the_current_year(): void
    {
        $this->travelTo(Carbon::parse('2026-03-02'));
        Activity::factory()->create(['starts_on' => '2026-03-05', 'ends_on' => '2026-03-05']);

        $this->get(route('agenda'))
            ->assertSee('Donderdag <span class="text-color-lc">5 maart</span>', false);
    }

    public function test_day_headers_show_the_year_when_it_is_not_the_current_year(): void
    {
        $this->travelTo(Carbon::parse('2026-12-30'));
        Activity::factory()->create(['starts_on' => '2026-12-31', 'ends_on' => '2026-12-31']);
        Activity::factory()->create(['starts_on' => '2027-01-02', 'ends_on' => '2027-01-02']);

        $this->get(route('agenda'))
            ->assertSee(__('agenda/index.tomorrow').' <span class="text-color-lc">31 december</span>', false)
            ->assertSee('Zaterdag <span class="text-color-lc">2 januari 2027</span>', false);
    }

    public function test_agenda_shows_upcoming_activities_far_ahead(): void
    {
        $farAhead = Activity::factory()->create(['starts_on' => today()->addMonths(3), 'ends_on' => today()->addMonths(3)]);

        $this->get(route('agenda'))
            ->assertSee($farAhead->title)
            ->assertDontSee(__('agenda/index.empty'))
            ->assertDontSee(__('agenda/index.next'))
            ->assertDontSee('noindex');
    }

    public function test_page_ends_after_the_last_day_with_the_paginate_count(): void
    {
        $this->travelTo(Carbon::parse('2026-10-27'));
        Activity::factory()->count(Activity::PAGINATE_COUNT - 1)->create(['starts_on' => '2026-10-28', 'ends_on' => '2026-10-28']);
        Activity::factory()->count(2)->create(['starts_on' => '2026-11-05', 'ends_on' => '2026-11-05']);
        $nextPage = Activity::factory()->create(['starts_on' => '2026-11-12', 'ends_on' => '2026-11-12']);

        Livewire::test('pages::agenda.index')
            ->assertSet('activities', fn ($activities) => $activities->count() === Activity::PAGINATE_COUNT + 1)
            ->assertSet('nextStart', fn ($date) => $date->format('Y-m-d') === '2026-11-06')
            ->assertDontSee($nextPage->title)
            ->assertSee(route('agenda', ['vanaf' => '2026-11-06']), false);
    }

    public function test_next_page_starts_at_the_given_date(): void
    {
        $this->travelTo(Carbon::parse('2026-10-27'));
        $before = Activity::factory()->create(['starts_on' => '2026-11-05', 'ends_on' => '2026-11-05']);
        $after = Activity::factory()->create(['starts_on' => '2026-11-12', 'ends_on' => '2026-11-12']);

        $this->get(route('agenda', ['vanaf' => '2026-11-06']))
            ->assertSee('Donderdag <span class="text-color-lc">12 november</span>', false)
            ->assertSee($after->title)
            ->assertDontSee($before->title)
            ->assertSee(route('agenda'), false)
            ->assertSee(__('agenda/index.back_to_today'))
            ->assertSee('<meta content="noindex" name="robots">', false);
    }

    public function test_activity_running_into_the_page_is_listed_under_its_first_day(): void
    {
        $this->travelTo(Carbon::parse('2026-10-27'));
        $running = Activity::factory()->create(['starts_on' => '2026-11-01', 'ends_on' => '2026-11-04']);

        Livewire::test('pages::agenda.index', ['start' => '2026-11-03'])
            ->assertSet('activitiesByDay', fn ($days) => $days->keys()->all() === ['2026-11-03'])
            ->assertSee($running->title);
    }

    public function test_start_before_today_or_invalid_starts_today(): void
    {
        $this->travelTo(Carbon::parse('2026-10-27'));
        $past = Activity::factory()->create(['starts_on' => '2026-10-20', 'ends_on' => '2026-10-20']);

        foreach (['2026-10-01', 'morgen', '2026-13-45x'] as $start) {
            $this->get(route('agenda', ['vanaf' => $start]))
                ->assertOk()
                ->assertDontSee($past->title)
                ->assertDontSee(__('agenda/index.back_to_today'));
        }
    }

    public function test_next_page_link_keeps_the_filters(): void
    {
        $this->travelTo(Carbon::parse('2026-10-27'));
        $user = User::factory()->create();
        Activity::factory()->for($user)->count(Activity::PAGINATE_COUNT + 1)->sequence(
            fn ($sequence) => ['starts_on' => today()->addDays($sequence->index + 1), 'ends_on' => today()->addDays($sequence->index + 1)]
        )->create(['category' => ActivityCategory::MUSIC]);
        Activity::factory()->count(Activity::PAGINATE_COUNT)->create(['category' => ActivityCategory::CLUB]);

        $this->get(route('agenda.user.category', [$user, ActivityCategory::MUSIC->value]))
            ->assertSee(route('agenda.user.category', [$user, ActivityCategory::MUSIC->value, 'vanaf' => '2026-11-17']), false);

        $this->get(route('agenda.category', ActivityCategory::DANCE->value))
            ->assertDontSee(__('agenda/index.next'));
    }
}
