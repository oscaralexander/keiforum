<?php

namespace App\Providers;

use App\Events\MessageCreated;
use App\Events\PostLiked;
use App\Events\PostSaving;
use App\Listeners\CheckLikeThreshold;
use App\Listeners\SendMentionNotification;
use App\Listeners\SendNewMessageNotification;
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
        PostLiked::class => [
            CheckLikeThreshold::class,
        ],
        PostSaving::class => [
            SendMentionNotification::class,
        ],
    ];

    public function boot(): void {}

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
