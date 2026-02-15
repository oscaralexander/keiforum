<?php

namespace App\Mail;

use App\Models\Post;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LikeThresholdReached extends Mailable implements ShouldQueue
{
    public $count;
    public $post;

    public function __construct(Post $post, int $count)
    {
        $this->count = $count;
        $this->post = $post;
        $this->post->loadMissing(['topic', 'topic.forum']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail/like_threshold_reached.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.' . app()->getLocale() . '.post.like-threshold-reached',
            with: [

            ]
        );

        return new Content(
            markdown: 'emails.posts.threshold-reached',
            with: [
                'postTitle' => $this->post->title,
                'likeCount' => $this->count,
            ],
        );
    }
}