<?php

namespace App\Mail;

use App\Models\Message;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewMessageReceived extends Mailable
{
    public $msg;

    public function __construct(Message $msg)
    {
        $this->msg = $msg;
        $this->msg->loadMissing(['conversation', 'user']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail/new_message.subject', ['user' => $this->msg->user->username]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.' . app()->getLocale() . '.conversation.new-message',
        );
    }
}
