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

    public function test_members_who_are_not_organizers_cannot_create_activities(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('agenda.create'))
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

    public function test_dates_default_to_today(): void
    {
        Livewire::actingAs(User::factory()->organizer()->create())
            ->test('pages::agenda.create')
            ->assertSet('form.starts_on', today()->format('Y-m-d'))
            ->assertSet('form.ends_on', today()->format('Y-m-d'))
            ->assertSet('form.price', '0');
    }

    public function test_end_date_follows_start_date_until_changed(): void
    {
        $component = Livewire::actingAs(User::factory()->organizer()->create())
            ->test('pages::agenda.create')
            ->set('form.starts_on', '2099-05-01')
            ->assertSet('form.ends_on', '2099-05-01')
            ->set('form.ends_on', '2099-05-03')
            ->set('form.starts_on', '2099-05-02')
            ->assertSet('form.ends_on', '2099-05-03');

        $component->set('form.starts_on', '2099-05-10')
            ->assertSet('form.ends_on', '2099-05-10');
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

    public function test_end_date_cannot_be_before_start_date(): void
    {
        $this->validForm()
            ->set('form.ends_on', today()->format('Y-m-d'))
            ->call('submit')
            ->assertHasErrors('form.ends_on');
    }

    public function test_same_day_end_time_must_be_after_start_time(): void
    {
        $this->validForm()
            ->set('form.ends_at', '19:00')
            ->call('submit')
            ->assertHasErrors('form.ends_at');
    }

    public function test_end_time_may_be_earlier_when_activity_ends_the_next_day(): void
    {
        $this->validForm()
            ->set('form.ends_on', today()->addWeek()->addDay()->format('Y-m-d'))
            ->set('form.ends_at', '04:00')
            ->call('submit')
            ->assertHasNoErrors();
    }

    public function test_end_time_is_optional(): void
    {
        $this->validForm()
            ->set('form.ends_at', '')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertNull(Activity::sole()->ends_at);
    }

    public function test_negative_price_is_rejected(): void
    {
        $this->validForm()
            ->set('form.price', '-5')
            ->call('submit')
            ->assertHasErrors('form.price');
    }
}
