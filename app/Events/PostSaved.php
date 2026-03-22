<?php

namespace App\Events;

use App\Models\Post;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostSaved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Post $post, public ?string $oldBody = null) {}
}
