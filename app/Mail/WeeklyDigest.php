<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class WeeklyDigest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Retried with a backoff, so hitting the mail provider's rate limit
     * doesn't make members miss their digest.
     */
    public int $tries = 5;

    /**
     * @param  array{
     *     active_topics_count: int,
     *     new_members_count: int,
     *     new_topics: list<array{title: string, url: string, forum: string, posts_count: int, avatar_url: string, username: string}>,
     *     popular_topics: list<array{title: string, url: string, forum: string, posts_count: int, avatar_url: string, username: string}>,
     * }  $digest
     */
    public function __construct(public User $user, public array $digest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail/digest.subject'),
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: ['List-Unsubscribe' => '<'.$this->unsubscribeUrl().'>'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.'.app()->getLocale().'.digest.weekly',
            with: ['unsubscribeUrl' => $this->unsubscribeUrl()],
        );
    }

    public function unsubscribeUrl(): string
    {
        return URL::signedRoute('digest.unsubscribe', $this->user);
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900, 1800];
    }

    public function attachments(): array
    {
        return [];
    }
}
