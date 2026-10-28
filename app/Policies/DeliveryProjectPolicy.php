<?php

namespace App\Policies;

use App\Models\User;

class DeliveryProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('delivery_projects.view');
    }

    public function view(User $user): bool
    {
        return $user->can('delivery_projects.view');
    }

    public function create(User $user): bool
    {
        return $user->can('delivery_projects.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('delivery_projects.manage');
    }

    public function delete(User $user): bool
    {
        return $user->can('delivery_projects.manage');
    }
}
