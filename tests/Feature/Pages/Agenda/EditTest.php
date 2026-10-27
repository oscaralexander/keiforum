<?php

namespace Tests\Feature\Pages\Agenda;

use App\Enums\ActivityCategory;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function activity(array $attributes = []): Activity
    {
        return Activity::factory()->create([
            'title' => 'Jazz op de Hof',
            'category' => ActivityCategory::MUSIC,
            'starts_on' => '2099-10-14',
            'ends_on' => '2099-10-14',
            'starts_at' => '20:00',
            'ends_at' => '23:30',
            'price' => 1250,
            ...$attributes,
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get($this->activity()->editUrl())->assertRedirect(route('login'));
    }

    public function test_other_users_cannot_edit_the_activity(): void
    {
        $this->actingAs(User::factory()->create())
            ->get($this->activity()->editUrl())
            ->assertForbidden();
    }

    public function test_owner_can_open_the_edit_page(): void
    {
        $activity = $this->activity();

        $this->actingAs($activity->user)
            ->get($activity->editUrl())
            ->assertOk()
            ->assertSee(__('agenda/edit.title'));
    }

    public function test_admins_can_open_the_edit_page(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get($this->activity()->editUrl())
            ->assertOk();
    }

    public function test_unknown_activity_returns_not_found(): void
    {
        $activity = $this->activity();

        $this->actingAs($activity->user)
            ->get(route('agenda.edit', [$activity->user, '2099-10-14', 'bestaat-niet']))
            ->assertNotFound();
    }

    public function test_form_is_filled_with_the_activity(): void
    {
        $activity = $this->activity();

        Livewire::actingAs($activity->user)
            ->test('pages::agenda.edit', ['user' => $activity->user, 'date' => '2099-10-14', 'slug' => $activity->slug])
            ->assertSet('form.title', 'Jazz op de Hof')
            ->assertSet('form.category', ActivityCategory::MUSIC->value)
            ->assertSet('form.starts_on', '2099-10-14')
            ->assertSet('form.starts_at', '20:00')
            ->assertSet('form.ends_at', '23:30')
            ->assertSet('form.price', '12.5')
            ->assertSet('form.website_url', '');
    }

    public function test_activity_is_updated_without_replacing_the_poster(): void
    {
        $activity = $this->activity(['poster_path' => 'posters/old.webp']);
        Storage::disk('public')->put('posters/old.webp', 'poster');

        Livewire::actingAs($activity->user)
            ->test('pages::agenda.edit', ['user' => $activity->user, 'date' => '2099-10-14', 'slug' => $activity->slug])
            ->set('form.location_name', 'De Kelder')
            ->set('form.price', '15')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect($activity->url());

        $activity->refresh();

        $this->assertSame('De Kelder', $activity->location_name);
        $this->assertSame(1500, $activity->price);
        $this->assertSame('jazz-op-de-hof', $activity->slug);
        $this->assertSame('posters/old.webp', $activity->poster_path);
        Storage::disk('public')->assertExists('posters/old.webp');
    }

    public function test_new_poster_replaces_the_old_one(): void
    {
        $activity = $this->activity(['poster_path' => 'posters/old.webp']);
        Storage::disk('public')->put('posters/old.webp', 'poster');

        Livewire::actingAs($activity->user)
            ->test('pages::agenda.edit', ['user' => $activity->user, 'date' => '2099-10-14', 'slug' => $activity->slug])
            ->set('poster', UploadedFile::fake()->image('poster.jpg', 800, 1000))
            ->call('submit')
            ->assertHasNoErrors();

        $activity->refresh();

        $this->assertNotSame('posters/old.webp', $activity->poster_path);
        Storage::disk('public')->assertExists($activity->poster_path);
        Storage::disk('public')->assertMissing('posters/old.webp');
    }

    public function test_changing_the_title_and_date_moves_the_activity_to_a_new_url(): void
    {
        $activity = $this->activity();

        $component = Livewire::actingAs($activity->user)
            ->test('pages::agenda.edit', ['user' => $activity->user, 'date' => '2099-10-14', 'slug' => $activity->slug])
            ->set('form.title', 'Blues op de Hof')
            ->set('form.starts_on', '2099-10-15')
            ->call('submit')
            ->assertHasNoErrors();

        $activity->refresh();

        $this->assertSame('blues-op-de-hof', $activity->slug);
        $this->assertSame('2099-10-15', $activity->ends_on->format('Y-m-d'));
        $component->assertRedirect(url('/agenda/@'.$activity->user->username.'/2099-10-15/blues-op-de-hof'));
    }

    public function test_slug_stays_unique_among_the_users_activities(): void
    {
        $activity = $this->activity();
        Activity::factory()->for($activity->user)->create([
            'title' => 'Blues op de Hof',
            'starts_on' => '2099-10-14',
            'ends_on' => '2099-10-14',
        ]);

        Livewire::actingAs($activity->user)
            ->test('pages::agenda.edit', ['user' => $activity->user, 'date' => '2099-10-14', 'slug' => $activity->slug])
            ->set('form.title', 'Blues op de Hof')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame('blues-op-de-hof-2', $activity->refresh()->slug);
    }

    public function test_running_activity_keeps_its_start_date_in_the_past(): void
    {
        $activity = $this->activity([
            'starts_on' => today()->subDay(),
            'ends_on' => today()->addDay(),
        ]);

        Livewire::actingAs($activity->user)
            ->test('pages::agenda.edit', ['user' => $activity->user, 'date' => today()->subDay()->format('Y-m-d'), 'slug' => $activity->slug])
            ->set('form.title', 'Jazzweekend')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame('Jazzweekend', $activity->refresh()->title);
    }

    public function test_start_date_cannot_be_moved_into_the_past(): void
    {
        $activity = $this->activity();

        Livewire::actingAs($activity->user)
            ->test('pages::agenda.edit', ['user' => $activity->user, 'date' => '2099-10-14', 'slug' => $activity->slug])
            ->set('form.starts_on', today()->subDay()->format('Y-m-d'))
            ->call('submit')
            ->assertHasErrors('form.starts_on');
    }

    public function test_other_users_cannot_submit_changes(): void
    {
        $activity = $this->activity();

        Livewire::actingAs(User::factory()->create())
            ->test('pages::agenda.edit', ['user' => $activity->user, 'date' => '2099-10-14', 'slug' => $activity->slug])
            ->assertForbidden();

        $this->assertSame('Jazz op de Hof', $activity->refresh()->title);
    }

    public function test_owner_can_delete_the_activity_and_its_poster(): void
    {
        $activity = $this->activity(['poster_path' => 'posters/old.webp']);
        Storage::disk('public')->put('posters/old.webp', 'poster');
        $activity->attendees()->attach(User::factory()->create(), ['status' => 'going']);

        Livewire::actingAs($activity->user)
            ->test('pages::agenda.edit', ['user' => $activity->user, 'date' => '2099-10-14', 'slug' => $activity->slug])
            ->assertSee(__('ui.delete'))
            ->call('delete')
            ->assertRedirect(route('agenda'));

        $this->assertModelMissing($activity);
        Storage::disk('public')->assertMissing('posters/old.webp');
    }

    public function test_admins_can_delete_the_activity(): void
    {
        $activity = $this->activity();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test('pages::agenda.edit', ['user' => $activity->user, 'date' => '2099-10-14', 'slug' => $activity->slug])
            ->call('delete')
            ->assertRedirect(route('agenda'));

        $this->assertModelMissing($activity);
    }
}
