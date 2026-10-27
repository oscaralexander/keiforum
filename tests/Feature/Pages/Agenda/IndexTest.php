<?php

namespace Tests\Feature\Pages\Agenda;

use App\Enums\ActivityCategory;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_create_button_is_only_shown_to_members(): void
    {
        $this->get(route('agenda'))->assertDontSee(route('agenda.create'));

        $this->actingAs(User::factory()->create())
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
                today()->translatedFormat('j F Y'),
                __('agenda/index.tomorrow'),
                today()->addDay()->translatedFormat('j F Y'),
            ]);
    }
}
