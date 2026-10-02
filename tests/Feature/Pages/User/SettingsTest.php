<?php

namespace Tests\Feature\Pages\User;

use App\Mail\ConfirmEmailChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attributes = []): User
    {
        return User::factory()->create([
            'email' => 'oud@example.com',
            'password' => Hash::make('Huidig123'),
            ...$attributes,
        ])->fresh();
    }

    public function test_settings_page_has_no_username_field(): void
    {
        $this->actingAs($this->user())
            ->get(route('settings'))
            ->assertOk()
            ->assertDontSee(__('user/register.form.username.label'))
            ->assertSee('x-show="$wire.password_new || $wire.email.toLowerCase() !== \'oud@example.com\'"', false);
    }

    public function test_current_password_field_is_hidden_for_google_users(): void
    {
        $this->actingAs($this->user(['password' => null, 'google_id' => 'google-123']))
            ->get(route('settings'))
            ->assertOk()
            ->assertDontSee(__('user/settings.form.password_current.label'));
    }

    public function test_password_can_be_changed_with_current_password(): void
    {
        $user = $this->user();

        Livewire::actingAs($user)
            ->test('pages::user.settings')
            ->set('password_new', 'Nieuw12345')
            ->set('password_current', 'Huidig123')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('password_new', '')
            ->assertSet('password_current', '');

        $this->assertTrue(Hash::check('Nieuw12345', $user->fresh()->password));
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = $this->user();

        Livewire::actingAs($user)
            ->test('pages::user.settings')
            ->set('password_new', 'Nieuw12345')
            ->call('submit')
            ->assertHasErrors(['password_current' => 'required']);

        $this->assertTrue(Hash::check('Huidig123', $user->fresh()->password));
    }

    public function test_password_change_rejects_wrong_current_password(): void
    {
        $user = $this->user();

        Livewire::actingAs($user)
            ->test('pages::user.settings')
            ->set('password_new', 'Nieuw12345')
            ->set('password_current', 'Fout12345')
            ->call('submit')
            ->assertHasErrors(['password_current' => 'current_password']);

        $this->assertTrue(Hash::check('Huidig123', $user->fresh()->password));
    }

    public function test_new_password_must_be_strong(): void
    {
        Livewire::actingAs($this->user())
            ->test('pages::user.settings')
            ->set('password_new', 'zwak')
            ->set('password_current', 'Huidig123')
            ->call('submit')
            ->assertHasErrors('password_new');
    }

    public function test_google_user_can_set_password_without_current_password(): void
    {
        $user = $this->user(['password' => null, 'google_id' => 'google-123']);

        Livewire::actingAs($user)
            ->test('pages::user.settings')
            ->set('password_new', 'Nieuw12345')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('Nieuw12345', $user->fresh()->password));
    }

    public function test_saving_without_changes_needs_no_current_password(): void
    {
        $user = $this->user();

        Livewire::actingAs($user)
            ->test('pages::user.settings')
            ->set('isSubscribedToDigest', false)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertFalse($user->fresh()->is_subscribed_to_digest);
    }

    public function test_email_change_sends_confirmation_to_new_address(): void
    {
        Mail::fake();
        $user = $this->user();

        Livewire::actingAs($user)
            ->test('pages::user.settings')
            ->set('email', 'nieuw@example.com')
            ->set('password_current', 'Huidig123')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('email', 'oud@example.com')
            ->assertSee('nieuw@example.com');

        Mail::assertSent(ConfirmEmailChange::class, fn (ConfirmEmailChange $mail) => $mail->hasTo('nieuw@example.com') && $mail->newEmail === 'nieuw@example.com');
        $this->assertSame('oud@example.com', $user->fresh()->email);
    }

    public function test_email_change_requires_current_password(): void
    {
        Mail::fake();

        Livewire::actingAs($this->user())
            ->test('pages::user.settings')
            ->set('email', 'nieuw@example.com')
            ->call('submit')
            ->assertHasErrors(['password_current' => 'required']);

        Mail::assertNothingSent();
    }

    public function test_email_must_not_belong_to_another_account(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'bezet@example.com']);

        Livewire::actingAs($this->user())
            ->test('pages::user.settings')
            ->set('email', 'bezet@example.com')
            ->set('password_current', 'Huidig123')
            ->call('submit')
            ->assertHasErrors(['email' => 'unique']);

        Mail::assertNothingSent();
    }

    public function test_same_email_in_other_case_is_not_a_change(): void
    {
        Mail::fake();

        Livewire::actingAs($this->user())
            ->test('pages::user.settings')
            ->set('email', 'OUD@example.com')
            ->call('submit')
            ->assertHasNoErrors();

        Mail::assertNothingSent();
    }

    public function test_confirmation_mail_contains_signed_link(): void
    {
        $mail = new ConfirmEmailChange($this->user(), 'nieuw@example.com');

        $mail->assertHasSubject(__('mail/user.confirm-email-change.subject'));
        $mail->assertSeeInHtml('e-mailadres-bevestigen');
        $mail->assertSeeInHtml('signature=');
    }

    public function test_confirmation_link_changes_email(): void
    {
        $user = $this->user();
        $url = URL::temporarySignedRoute('confirm-email-change', now()->addDay(), ['user' => $user, 'email' => 'nieuw@example.com']);

        $this->get($url)
            ->assertOk()
            ->assertSee(__('user/confirm_email_change.success_title'));

        $this->assertSame('nieuw@example.com', $user->fresh()->email);
    }

    public function test_tampered_or_expired_confirmation_link_is_rejected(): void
    {
        $user = $this->user();
        $url = URL::temporarySignedRoute('confirm-email-change', now()->addDay(), ['user' => $user, 'email' => 'nieuw@example.com']);
        $expiredUrl = URL::temporarySignedRoute('confirm-email-change', now()->subMinute(), ['user' => $user, 'email' => 'nieuw@example.com']);

        $this->get(str_replace('nieuw%40example.com', 'aanvaller%40example.com', $url))->assertForbidden();
        $this->get($expiredUrl)->assertForbidden();

        $this->assertSame('oud@example.com', $user->fresh()->email);
    }

    public function test_confirmation_fails_when_email_was_taken_meanwhile(): void
    {
        $user = $this->user();
        $url = URL::temporarySignedRoute('confirm-email-change', now()->addDay(), ['user' => $user, 'email' => 'nieuw@example.com']);
        User::factory()->create(['email' => 'nieuw@example.com']);

        $this->get($url)
            ->assertOk()
            ->assertSee(__('user/confirm_email_change.error_title'));

        $this->assertSame('oud@example.com', $user->fresh()->email);
    }
}
