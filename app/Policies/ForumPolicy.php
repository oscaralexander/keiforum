<?php

namespace App\Policies;

use App\Models\Forum;
use App\Models\User;

class ForumPolicy
{
    /**
     * Anyone may start a topic in an open forum (guests are sent to the
     * login page), but only admins may start one in a locked forum. Topics
     * in a hidden forum are only created by the application itself.
     */
    public function createTopic(?User $user, Forum $forum): bool
    {
        if ($forum->is_hidden) {
            return false;
        }

        return ! $forum->is_locked || (bool) $user?->is_admin;
    }
}
