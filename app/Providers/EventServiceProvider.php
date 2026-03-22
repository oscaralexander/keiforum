<?php

namespace App\Providers;

use App\Events\MessageCreated;
use App\Events\PostCreated;
use App\Events\PostLiked;
use App\Events\PostSaved;
use App\Listeners\CheckLikeThreshold;
use App\Listeners\SendMentionNotification;
use App\Listeners\SendNewMessageNotification;
use App\Listeners\SendNewPostNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Registered::class => [
        //     SendEmailVerificationNotification::class,
        // ],
        MessageCreated::class => [
            SendNewMessageNotification::class,
        ],
        PostCreated::class => [
            SendNewPostNotification::class,
        ],
        PostLiked::class => [
            CheckLikeThreshold::class,
        ],
        PostSaved::class => [
            SendMentionNotification::class,
        ],
    ];

    public function boot(): void {}

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
