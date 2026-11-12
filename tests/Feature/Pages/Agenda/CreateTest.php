<?php

namespace Tests\Feature\Pages\Agenda;

use App\Enums\ActivityCategory;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function validForm(): Testable
    {
        return Livewire::actingAs(User::factory()->organizer()->create())
            ->test('pages::agenda.create')
            ->set('form.title', 'Jazz op de Hof')
            ->set('form.description', '<p>Live jazz</p>')
            ->set('form.category', ActivityCategory::MUSIC->value)
            ->set('form.starts_on', today()->addWeek()->format('Y-m-d'))
            ->set('form.starts_at', '20:00')
            ->set('form.ends_at', '23:30')
            ->set('form.location_name', 'De Kelder')
            ->set('form.location_address', 'Kleine Koppel 3, Amersfoort')
            ->set('form.price', '12,50')
            ->set('poster', UploadedFile::fake()->image('poster.jpg', 800, 1000));
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('agenda.create'))->assertRedirect(route('login'));
    }

    public function test_unverified_users_cannot_create_activities(): void
    {
        $this->actingAs(User::factory()->unverified()->organizer()->create())
            ->get(route('agenda.create'))
            ->assertForbidden();
    }

    public function test_members_who_are_not_organizers_see_the_request_instead_of_the_form(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('agenda.create'))
            ->assertOk()
            ->assertSee(__('agenda/create.request.text'), false)
            ->assertSee(__('agenda/create.request.submit'))
            ->assertDontSee(__('agenda/create.submit'));
    }

    public function test_member_can_request_the_organizer_right(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::agenda.create')
            ->call('requestOrganizer')
            ->assertSee(__('agenda/create.request.pending'))
            ->assertDontSee(__('agenda/create.request.submit'));

        $this->assertNotNull($user->refresh()->organizer_requested_at);
    }

    public function test_pending_request_shows_a_disabled_button(): void
    {
        $this->actingAs(User::factory()->create(['organizer_requested_at' => now()]))
            ->get(route('agenda.create'))
            ->assertSeeInOrder(['disabled', __('agenda/create.request.pending')], false);
    }

    public function test_requesting_again_keeps_the_original_date(): void
    {
        $user = User::factory()->create(['organizer_requested_at' => now()->subWeek()]);

        Livewire::actingAs($user)->test('pages::agenda.create')->call('requestOrganizer');

        $this->assertTrue($user->refresh()->organizer_requested_at->isSameDay(now()->subWeek()));
    }

    public function test_members_who_are_not_organizers_cannot_submit_activities(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test('pages::agenda.create')
            ->call('submit')
            ->assertForbidden();
    }

    public function test_organizers_cannot_request_the_right_again(): void
    {
        Livewire::actingAs(User::factory()->organizer()->create())
            ->test('pages::agenda.create')
            ->call('requestOrganizer')
            ->assertForbidden();
    }

    public function test_banned_organizers_cannot_create_activities(): void
    {
        $this->actingAs(User::factory()->organizer()->banned()->create())
            ->get(route('agenda.create'))
            ->assertForbidden();
    }

    public function test_admins_can_create_activities(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('agenda.create'))
            ->assertOk();
    }

    public function test_poster_instructions_mention_the_dimensions(): void
    {
        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('agenda.create'))
            ->assertOk()
            ->assertSee('Poster of flyer in A4-verhoudingen, bij voorkeur 1080 x 1528 pixels.');
    }

    public function test_location_search_is_shown_with_a_google_api_key(): void
    {
        config(['services.google.api_key' => 'test-key']);

        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('agenda.create'))
            ->assertOk()
            ->assertSee('maps.googleapis.com/maps/api/js?key=test-key', false)
            ->assertSee('placeAutocomplete(3)', false)
            ->assertSee(__('agenda/form.location.search_placeholder'));
    }

    public function test_location_search_is_hidden_without_a_google_api_key(): void
    {
        config(['services.google.api_key' => null]);

        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('agenda.create'))
            ->assertOk()
            ->assertDontSee('maps.googleapis.com', false)
            ->assertDontSee('placeAutocomplete', false)
            ->assertSee(__('agenda/form.location_name.placeholder'));
    }

    public function test_date_defaults_to_today(): void
    {
        Livewire::actingAs(User::factory()->organizer()->create())
            ->test('pages::agenda.create')
            ->assertSet('form.starts_on', today()->format('Y-m-d'))
            ->assertSet('form.price', '0');
    }

    public function test_location_is_taken_from_the_latest_activity(): void
    {
        $user = User::factory()->organizer()->create();
        Activity::factory()->for($user)->create([
            'location_name' => 'Oude Locatie',
            'created_at' => now()->subMonth(),
        ]);
        Activity::factory()->for($user)->create([
            'location_name' => 'Podium Fluor',
            'location_address' => 'Hendrik van Viandenstraat 11, Amersfoort',
            'location_place_id' => 'ChIJ-fluor',
            'location_latitude' => 52.1561,
            'location_longitude' => 5.3878,
            'created_at' => now()->subDay(),
        ]);
        Activity::factory()->create(['location_name' => 'Iemand Anders']);

        Livewire::actingAs($user)
            ->test('pages::agenda.create')
            ->assertSet('form.location_name', 'Podium Fluor')
            ->assertSet('form.location_address', 'Hendrik van Viandenstraat 11, Amersfoort')
            ->assertSet('form.location_place_id', 'ChIJ-fluor')
            ->assertSet('form.location_latitude', 52.1561)
            ->assertSet('form.location_longitude', 5.3878);
    }

    public function test_default_location_can_be_replaced(): void
    {
        $user = User::factory()->organizer()->create();
        Activity::factory()->for($user)->create([
            'location_name' => 'Podium Fluor',
            'location_place_id' => 'ChIJ-fluor',
            'location_latitude' => 52.1561,
            'location_longitude' => 5.3878,
        ]);

        Livewire::actingAs($user)
            ->test('pages::agenda.create')
            ->set('form.title', 'Jazz op de Hof')
            ->set('form.description', '<p>Live jazz</p>')
            ->set('form.category', ActivityCategory::MUSIC->value)
            ->set('form.starts_at', '20:00')
            ->set('form.location_name', 'De Kelder')
            ->set('form.location_address', 'Kleine Koppel 3, Amersfoort')
            ->set('form.location_place_id', 'ChIJ-kelder')
            ->set('form.location_latitude', 52.1601)
            ->set('form.location_longitude', 5.3801)
            ->call('submit')
            ->assertHasNoErrors();

        $activity = $user->activities()->latest('id')->first();

        $this->assertSame('De Kelder', $activity->location_name);
        $this->assertSame('ChIJ-kelder', $activity->location_place_id);
        $this->assertSame(52.1601, $activity->location_latitude);
    }

    public function test_location_is_empty_without_earlier_activities(): void
    {
        Livewire::actingAs(User::factory()->organizer()->create())
            ->test('pages::agenda.create')
            ->assertSet('form.location_name', '')
            ->assertSet('form.location_address', '')
            ->assertSet('form.location_latitude', null);
    }

    public function test_form_asks_for_a_single_date(): void
    {
        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('agenda.create'))
            ->assertOk()
            ->assertSee('Datum')
            ->assertDontSee('Begindatum')
            ->assertDontSee('Einddatum')
            ->assertSee('grid__col l:grid__col--span-6', false)
            ->assertSee('grid__col l:grid__col--span-3', false);
    }

    public function test_activity_is_created_with_a_poster_in_a4_proportions(): void
    {
        $this->validForm()
            ->call('submit')
            ->assertHasNoErrors();

        $activity = Activity::sole();

        $this->assertSame('Jazz op de Hof', $activity->title);
        $this->assertSame(ActivityCategory::MUSIC, $activity->category);
        $this->assertSame(1250, $activity->price);
        $this->assertSame('23:30', $activity->ends_at_time);
        $this->assertTrue($activity->starts_on->isSameDay($activity->ends_on));
        $this->assertNotNull($activity->published_at);

        Storage::disk('public')->assertExists($activity->poster_path);
        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($activity->poster_path));
        $this->assertSame([Activity::POSTER_WIDTH, Activity::POSTER_HEIGHT], [$width, $height]);
    }

    public function test_user_is_redirected_to_the_new_activity(): void
    {
        $component = $this->validForm()->call('submit');

        $component->assertRedirect(Activity::sole()->url());
    }

    public function test_required_fields_are_validated(): void
    {
        Livewire::actingAs(User::factory()->organizer()->create())
            ->test('pages::agenda.create')
            ->call('submit')
            ->assertHasErrors(['form.title', 'form.description', 'form.category', 'form.starts_at', 'form.location_name']);

        $this->assertSame(0, Activity::count());
    }

    public function test_browse_button_is_shown_until_a_poster_is_uploaded(): void
    {
        Livewire::actingAs(User::factory()->organizer()->create())
            ->test('pages::agenda.create')
            ->assertSee(__('ui.browse'))
            ->assertDontSee(__('agenda/form.poster.remove'))
            ->set('poster', UploadedFile::fake()->image('poster.jpg', 800, 1000))
            ->assertSee(__('agenda/form.poster.remove'))
            ->assertDontSee(__('ui.browse'));
    }

    public function test_uploaded_poster_can_be_removed(): void
    {
        $this->validForm()
            ->call('removePoster')
            ->assertSet('poster', null)
            ->assertSee(__('ui.browse'))
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertNull(Activity::sole()->poster_path);
    }

    public function test_poster_must_be_an_image(): void
    {
        $this->validForm()
            ->set('poster', UploadedFile::fake()->create('poster.pdf', 100, 'application/pdf'))
            ->call('submit')
            ->assertHasErrors('poster');
    }

    public function test_poster_is_optional(): void
    {
        $this->validForm()
            ->set('poster', null)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertNull(Activity::sole()->poster_path);
    }

    public function test_website_url_is_stored(): void
    {
        $this->validForm()
            ->set('form.website_url', 'https://www.podiumfluor.nl/agenda')
            ->call('submit')
            ->assertHasNoErrors();

        $activity = Activity::sole();

        $this->assertSame('https://www.podiumfluor.nl/agenda', $activity->website_url);
        $this->assertSame('podiumfluor.nl', $activity->website_host);
    }

    public function test_website_url_is_optional(): void
    {
        $this->validForm()
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertNull(Activity::sole()->website_url);
    }

    public function test_website_url_must_be_an_http_url(): void
    {
        $this->validForm()
            ->set('form.website_url', 'javascript:alert(1)')
            ->call('submit')
            ->assertHasErrors('form.website_url');

        $this->validForm()
            ->set('form.website_url', 'geen url')
            ->call('submit')
            ->assertHasErrors('form.website_url');
    }

    public function test_start_date_cannot_be_in_the_past(): void
    {
        $this->validForm()
            ->set('form.starts_on', today()->subDay()->format('Y-m-d'))
            ->call('submit')
            ->assertHasErrors('form.starts_on');
    }

    public function test_end_time_must_differ_from_start_time(): void
    {
        $this->validForm()
            ->set('form.ends_at', '20:00')
            ->call('submit')
            ->assertHasErrors('form.ends_at');
    }

    public function test_end_time_before_start_time_ends_the_next_day(): void
    {
        $this->validForm()
            ->set('form.starts_at', '23:00')
            ->set('form.ends_at', '04:00')
            ->call('submit')
            ->assertHasNoErrors();

        $activity = Activity::sole();

        $this->assertSame(today()->addWeek()->format('Y-m-d'), $activity->starts_on->format('Y-m-d'));
        $this->assertSame(today()->addWeek()->addDay()->format('Y-m-d'), $activity->ends_on->format('Y-m-d'));
        $this->assertFalse($activity->is_multi_day);
    }

    public function test_end_time_is_optional(): void
    {
        $this->validForm()
            ->set('form.ends_at', '')
            ->call('submit')
            ->assertHasNoErrors();

        $activity = Activity::sole();

        $this->assertNull($activity->ends_at);
        $this->assertTrue($activity->starts_on->isSameDay($activity->ends_on));
    }

    public function test_negative_price_is_rejected(): void
    {
        $this->validForm()
            ->set('form.price', '-5')
            ->call('submit')
            ->assertHasErrors('form.price');
    }
}
