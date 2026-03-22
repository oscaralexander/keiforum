<?php

namespace App\Listeners;

use App\Events\PostCreated;
use App\Mail\NewPostInTopic;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendNewPostNotification implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(PostCreated $event): void
    {
        $post = $event->post;
        $post->loadMissing(['topic', 'topic.forum', 'user']);

        $subscribers = $post->topic->subscribers()
            ->where('users.id', '!=', $post->user_id)
            ->get();

        foreach ($subscribers as $subscriber) {
            $pivot = $subscriber->pivot;

            $lastReadPostId = $pivot->last_read_post_id ?? 0;
            $lastNotifiedPostId = $pivot->last_notified_post_id ?? 0;

            if ($lastNotifiedPostId > $lastReadPostId) {
                continue;
            }

            Mail::to($subscriber->email)->send(new NewPostInTopic($post, $subscriber));

            $post->topic->trackedByUsers()->updateExistingPivot($subscriber->id, [
                'last_notified_post_id' => $post->id,
            ]);
        }
    }
}
