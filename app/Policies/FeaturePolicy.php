<?php

namespace App\Policies;

use App\Models\Feature;
use App\Models\User;

class FeaturePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('features.view');
    }

    public function view(User $user, Feature $feature): bool
    {
        return $user->can('features.view');
    }

    public function create(User $user): bool
    {
        return $user->can('features.manage');
    }

    public function update(User $user, Feature $feature): bool
    {
        return $user->can('features.manage');
    }

    public function delete(User $user, Feature $feature): bool
    {
        return $user->can('features.manage');
    }
}
