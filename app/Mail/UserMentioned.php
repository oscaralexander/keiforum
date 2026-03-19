<?php

namespace App\Mail;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserMentioned extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Post $post, public User $mentionedUser)
    {
        $this->post->loadMissing(['topic', 'topic.forum', 'user']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail/post/user_mentioned.subject', ['username' => $this->post->user->username]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.' . app()->getLocale() . '.post.user-mentioned',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
