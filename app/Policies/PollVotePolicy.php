<?php

namespace App\Policies;

use App\Models\PollVote;
use App\Models\User;

class PollVotePolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PollVote $pollVote): bool
    {
        return $user->id == $pollVote->user_id || $user->is_admin;
    }

    public function delete(User $user, PollVote $pollVote): bool
    {
        return $user->id == $pollVote->user_id || $user->is_admin;
    }
}
