<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $token
    ) {}

    public function attachments(): array
    {
        return [];
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.'.app()->getLocale().'.user.reset-password',
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail/user.reset-password.subject'),
        );
    }
}
