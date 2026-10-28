<?php

namespace Tests\Feature\Pages\User;

use App\Mail\ActivateAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_sends_the_activation_mail_and_shows_confirmation(): void
    {
        Mail::fake();

        Livewire::test('pages::user.register')
            ->set('email', 'nieuw@keiforum.nl')
            ->set('name', 'Nieuwe Gebruiker')
            ->set('password', 'Geheim123')
            ->set('terms', true)
            ->set('username', 'nieuwegebruiker')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('activationEmailSent', true)
            ->assertSee(__('user/register.activation_email_sent_title'))
            ->assertDontSee('user/register.activation_email_sent_title');

        Mail::assertSent(ActivateAccount::class, fn (ActivateAccount $mail): bool => $mail->hasTo('nieuw@keiforum.nl'));
    }
}
