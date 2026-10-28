<?php

namespace App\Policies;

use App\Models\User;

class StrategicPillarPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pillars.view');
    }

    public function view(User $user): bool
    {
        return $user->can('pillars.view');
    }

    public function create(User $user): bool
    {
        return $user->can('pillars.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('pillars.manage');
    }

    public function delete(User $user): bool
    {
        return $user->can('pillars.manage');
    }
}
