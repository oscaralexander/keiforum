<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\MessageCreated;
use App\Events\PostLiked;
use App\Listeners\CheckLikeThreshold;
use App\Listeners\SendNewMessageNotification;

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
    ];  

    public function boot(): void
    {
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}