<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    public function view(User $user, Group $group): bool
    {
        return $group->hasMember($user);
    }

    public function update(User $user, Group $group): bool
    {
        return $group->hasMember($user);
    }
}
