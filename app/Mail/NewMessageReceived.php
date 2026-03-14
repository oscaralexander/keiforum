<?php

namespace App\Mail;

use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewMessageReceived extends Mailable // implements ShouldQueue
{
    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
        $this->message->loadMissing(['conversation', 'user']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail/new_message.subject', ['user' => $this->message->user->username]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.' . app()->getLocale() . '.conversation.new-message',
        );
    }
}
