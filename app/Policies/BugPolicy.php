<?php

namespace App\Policies;

use App\Models\Bug;
use App\Models\User;

class BugPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bugs.view');
    }

    public function view(User $user, Bug $bug): bool
    {
        return $user->can('bugs.view');
    }

    public function create(User $user): bool
    {
        return $user->can('bugs.manage') || $user->can('bugs.update');
    }

    public function update(User $user, Bug $bug): bool
    {
        if ($user->can('bugs.manage')) {
            return true;
        }

        return $user->can('bugs.update')
            && ($bug->assignee_id === $user->id || $bug->reporter_id === $user->id);
    }

    public function delete(User $user, Bug $bug): bool
    {
        return $user->can('bugs.manage');
    }
}
