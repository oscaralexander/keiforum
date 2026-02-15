<?php

namespace App\Listeners;

use App\Events\PostLiked;
use App\Mail\PostThresholdReached;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class CheckLikeThreshold implements ShouldQueue
{
    public $queue = 'notifications'; 

    public function handle(PostLiked $event)
    {
        $post = $event->post->loadCount('likes');
        $likeCount = $post->likes_count;
        $thresholds = config('notifications.like_thresholds');

        if (in_array($likeCount, $thresholds)) {
            $user = $post->user;

            Mail::to($user->email)->send(
                new LikeThresholdReached($post, $likeCount)
            );
        }
    }
}