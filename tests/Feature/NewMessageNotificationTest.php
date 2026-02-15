<?php

namespace Tests\Feature;

use App\Events\MessageCreated;
use App\Listeners\SendNewMessageNotification;
use App\Mail\NewMessageReceived;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewMessageNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function testNotificationIsSentToOtherParticipants(): void
    {
        Mail::fake();

        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $conversation = Conversation::firstOrCreateForParticipants([$sender->id, $recipient->id]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $sender->id,
            'body' => 'Hello!',
        ]);

        $listener = new SendNewMessageNotification;
        $listener->handle(new MessageCreated($message));

        Mail::assertQueued(NewMessageReceived::class, function ($mail) use ($recipient) {
            return $mail->hasTo($recipient->email);
        });
    }

    public function testNotificationIsNotSentToMessageAuthor(): void
    {
        Mail::fake();

        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $conversation = Conversation::firstOrCreateForParticipants([$sender->id, $recipient->id]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $sender->id,
            'body' => 'Hello!',
        ]);

        $listener = new SendNewMessageNotification;
        $listener->handle(new MessageCreated($message));

        Mail::assertNotQueued(NewMessageReceived::class, function ($mail) use ($sender) {
            return $mail->hasTo($sender->email);
        });
    }

    public function testNotificationIsNotSentWhenAlreadyNotifiedAndNotRead(): void
    {
        Mail::fake();

        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $conversation = Conversation::firstOrCreateForParticipants([$sender->id, $recipient->id]);

        $conversation->users()->updateExistingPivot($recipient->id, [
            'last_notified_at' => now(),
            'last_read_at' => now()->subHour(),
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $sender->id,
            'body' => 'Another message',
        ]);

        $listener = new SendNewMessageNotification;
        $listener->handle(new MessageCreated($message));

        Mail::assertNotQueued(NewMessageReceived::class);
    }

    public function testNotificationIsSentWhenUserReadSinceLastNotification(): void
    {
        Mail::fake();

        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $conversation = Conversation::firstOrCreateForParticipants([$sender->id, $recipient->id]);

        $conversation->users()->updateExistingPivot($recipient->id, [
            'last_notified_at' => now()->subHour(),
            'last_read_at' => now(),
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $sender->id,
            'body' => 'New message after read',
        ]);

        $listener = new SendNewMessageNotification;
        $listener->handle(new MessageCreated($message));

        Mail::assertQueued(NewMessageReceived::class, function ($mail) use ($recipient) {
            return $mail->hasTo($recipient->email);
        });
    }

    public function testLastNotifiedAtIsUpdatedAfterSending(): void
    {
        Mail::fake();

        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $conversation = Conversation::firstOrCreateForParticipants([$sender->id, $recipient->id]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $sender->id,
            'body' => 'Hello!',
        ]);

        $this->freezeTime(function () use ($message, $conversation, $recipient) {
            $listener = new SendNewMessageNotification;
            $listener->handle(new MessageCreated($message));

            $pivot = $conversation->users()->where('user_id', $recipient->id)->first()->pivot;
            $this->assertEquals(now()->toDateTimeString(), $pivot->last_notified_at);
        });
    }

    public function testNotificationIsNotSentWhenNotifiedAndNeverRead(): void
    {
        Mail::fake();

        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $conversation = Conversation::firstOrCreateForParticipants([$sender->id, $recipient->id]);

        $conversation->users()->updateExistingPivot($recipient->id, [
            'last_notified_at' => now(),
            'last_read_at' => null,
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $sender->id,
            'body' => 'Another message',
        ]);

        $listener = new SendNewMessageNotification;
        $listener->handle(new MessageCreated($message));

        Mail::assertNotQueued(NewMessageReceived::class);
    }
}
