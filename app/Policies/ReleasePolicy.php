<?php

namespace App\Policies;

use App\Models\Release;
use App\Models\User;

class ReleasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('releases.view');
    }

    public function view(User $user, Release $release): bool
    {
        return $user->can('releases.view');
    }

    public function create(User $user): bool
    {
        return $user->can('releases.manage');
    }

    public function update(User $user, Release $release): bool
    {
        return $user->can('releases.manage');
    }

    public function delete(User $user, Release $release): bool
    {
        return $user->can('releases.manage');
    }
}
