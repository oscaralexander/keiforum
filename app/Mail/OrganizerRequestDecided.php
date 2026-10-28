<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrganizerRequestDecided extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public bool $isApproved) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __($this->isApproved ? 'mail/user.organizer-approved.subject' : 'mail/user.organizer-denied.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.'.app()->getLocale().'.user.organizer-request-decided',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
