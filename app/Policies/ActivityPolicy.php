<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    /**
     * Only organizers, who are granted the right by an admin, and admins
     * place activities.
     */
    public function create(User $user): bool
    {
        return $user->email_verified_at !== null
            && ! $user->is_banned
            && ($user->is_organizer || $user->is_admin);
    }

    /**
     * Members who cannot place activities yet can ask the admins for the
     * right to do so.
     */
    public function requestToCreate(User $user): bool
    {
        return $user->email_verified_at !== null
            && ! $user->is_banned
            && ! $user->is_organizer
            && ! $user->is_admin;
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
