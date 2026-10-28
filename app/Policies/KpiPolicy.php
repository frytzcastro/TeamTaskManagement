<?php

namespace App\Policies;

use App\Models\User;

class KpiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('kpis.view');
    }

    public function view(User $user): bool
    {
        return $user->can('kpis.view');
    }

    public function create(User $user): bool
    {
        return $user->can('kpis.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('kpis.manage');
    }

    public function delete(User $user): bool
    {
        return $user->can('kpis.manage');
    }
}
