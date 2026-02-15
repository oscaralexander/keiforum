<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ActivateAccount extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user
    ) {}

    public function attachments(): array
    {
        return [];
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.' . app()->getLocale() . '.user.activate-account',
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail/user.activate-account.subject'),
        );
    }
}
