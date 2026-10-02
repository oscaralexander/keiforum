<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ConfirmEmailChange extends Mailable
{
    use Queueable, SerializesModels;

    public const VALID_HOURS = 24;

    public function __construct(
        public User $user,
        public string $newEmail
    ) {}

    public function attachments(): array
    {
        return [];
    }

    public function confirmUrl(): string
    {
        return URL::temporarySignedRoute(
            'confirm-email-change',
            now()->addHours(self::VALID_HOURS),
            ['user' => $this->user, 'email' => $this->newEmail],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.'.app()->getLocale().'.user.confirm-email-change',
            with: ['confirmUrl' => $this->confirmUrl()],
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail/user.confirm-email-change.subject'),
        );
    }
}
