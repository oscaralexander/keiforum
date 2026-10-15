<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function create(User $user): bool
    {
        return $user->email_verified_at !== null && ! $user->is_banned;
    }

    public function update(User $user, Activity $activity): bool
    {
        return $user->id == $activity->user_id || $user->is_admin;
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $user->id == $activity->user_id || $user->is_admin;
    }

    /**
     * Whether the user can say they are going to a published activity that
     * has not ended yet.
     */
    public function attend(User $user, Activity $activity): bool
    {
        return ! $user->is_banned
            && $activity->published_at !== null
            && $activity->ends_on->copy()->endOfDay()->isFuture();
    }
}
