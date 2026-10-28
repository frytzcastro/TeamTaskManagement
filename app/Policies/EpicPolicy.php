<?php

namespace App\Policies;

use App\Models\Epic;
use App\Models\User;

class EpicPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('epics.view');
    }

    public function view(User $user, Epic $epic): bool
    {
        return $user->can('epics.view');
    }

    public function create(User $user): bool
    {
        return $user->can('epics.manage');
    }

    public function update(User $user, Epic $epic): bool
    {
        return $user->can('epics.manage');
    }

    public function delete(User $user, Epic $epic): bool
    {
        return $user->can('epics.manage');
    }
}
