<?php

namespace Tests\Feature\Pages\User;

use App\Mail\ResetPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_is_reachable_for_guests(): void
    {
        $this->get(route('forgot-password'))->assertOk();
    }

    public function test_submitting_a_known_email_sends_the_reset_mail(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'lid@keiforum.nl']);

        Livewire::test('pages::user.forgot-password')
            ->set('email', 'lid@keiforum.nl')
            ->call('submit')
            ->assertSet('resetEmailSent', true);

        Mail::assertSent(ResetPassword::class, fn (ResetPassword $mail): bool => $mail->hasTo($user->email) && $mail->token !== '');
    }

    public function test_submitting_an_unknown_email_shows_confirmation_without_sending_mail(): void
    {
        Mail::fake();

        Livewire::test('pages::user.forgot-password')
            ->set('email', 'niemand@keiforum.nl')
            ->call('submit')
            ->assertSet('resetEmailSent', true);

        Mail::assertNothingSent();
    }

    public function test_email_is_required_and_must_be_valid(): void
    {
        Mail::fake();

        Livewire::test('pages::user.forgot-password')
            ->set('email', '')
            ->call('submit')
            ->assertHasErrors(['email' => 'required'])
            ->assertSet('resetEmailSent', false);

        Livewire::test('pages::user.forgot-password')
            ->set('email', 'geen-email')
            ->call('submit')
            ->assertHasErrors(['email' => 'email'])
            ->assertSet('resetEmailSent', false);

        Mail::assertNothingSent();
    }

    public function test_login_page_links_to_the_forgot_password_page(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('forgot-password'));
    }
}
