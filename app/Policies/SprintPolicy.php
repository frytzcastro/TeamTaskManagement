<?php

namespace App\Policies;

use App\Models\Sprint;
use App\Models\User;

class SprintPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sprints.view');
    }

    public function view(User $user, Sprint $sprint): bool
    {
        return $user->can('sprints.view');
    }

    public function create(User $user): bool
    {
        return $user->can('sprints.manage');
    }

    public function update(User $user, Sprint $sprint): bool
    {
        return $user->can('sprints.manage');
    }

    public function delete(User $user, Sprint $sprint): bool
    {
        return $user->can('sprints.manage');
    }
}
