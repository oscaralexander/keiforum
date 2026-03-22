<?php

namespace App\Mail;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewPostInTopic extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Post $post, public User $subscriber)
    {
        $this->post->loadMissing(['topic', 'topic.forum', 'user']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail/topic/new_post.subject', ['title' => $this->post->topic->title]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.'.app()->getLocale().'.topic.new-post',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
