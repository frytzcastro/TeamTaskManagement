<?php

namespace App\Policies;

use App\Models\User;

class InitiativePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('initiatives.view');
    }

    public function view(User $user): bool
    {
        return $user->can('initiatives.view');
    }

    public function create(User $user): bool
    {
        return $user->can('initiatives.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('initiatives.manage');
    }

    public function delete(User $user): bool
    {
        return $user->can('initiatives.manage');
    }
}
