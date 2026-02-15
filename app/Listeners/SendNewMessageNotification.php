<?php

namespace App\Listeners;

use App\Events\MessageCreated;
use App\Mail\NewMessageReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendNewMessageNotification implements ShouldQueue
{
    public $queue = 'notifications';

    public function handle(MessageCreated $event): void
    {
        $message = $event->message;
        $conversation = $message->conversation;

        $participants = $conversation->otherParticipants($message->user);

        foreach ($participants as $participant) {
            $pivot = $conversation->users()->where('user_id', $participant->id)->first()?->pivot;

            if ($pivot && $pivot->last_notified_at !== null && (
                $pivot->last_read_at === null || $pivot->last_notified_at > $pivot->last_read_at
            )) {
                continue;
            }

            Mail::to($participant->email)->send(new NewMessageReceived($message));

            $conversation->users()->updateExistingPivot($participant->id, [
                'last_notified_at' => now(),
            ]);
        }
    }
}
