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
        $film = Activity::factory()->create(['category' => ActivityCategory::FILM]);

        $this->get('/agenda/club')
            ->assertOk()
            ->assertSee($club->title)
            ->assertDontSee($film->title);
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
        $ownFilm = Activity::factory()->for($venue)->create(['category' => ActivityCategory::FILM]);
        $otherClub = Activity::factory()->create(['category' => ActivityCategory::CLUB]);

        $this->get('/agenda/@'.$venue->username.'/club')
            ->assertOk()
            ->assertSee($ownClub->title)
            ->assertDontSee($ownFilm->title)
            ->assertDontSee($otherClub->title);
    }

    public function test_category_filters_keep_the_user(): void
    {
        $venue = User::factory()->create();

        $this->get('/agenda/@'.$venue->username)
            ->assertSee('href="'.route('agenda.user.category', [$venue, 'live-muziek']).'"', false);
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
            ->assertSee(route('agenda.create'));
    }

    public function test_empty_agenda_shows_message(): void
    {
        $this->get(route('agenda'))->assertSee(__('agenda/index.empty'));
    }
}
