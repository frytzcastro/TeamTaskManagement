<?php

namespace App\Policies;

use App\Models\BusinessProblem;
use App\Models\User;

class BusinessProblemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('business_problems.view');
    }

    public function view(User $user, BusinessProblem $businessProblem): bool
    {
        return $user->can('business_problems.view');
    }

    public function create(User $user): bool
    {
        return $user->can('business_problems.manage');
    }

    public function update(User $user, BusinessProblem $businessProblem): bool
    {
        return $user->can('business_problems.manage');
    }

    public function delete(User $user, BusinessProblem $businessProblem): bool
    {
        return $user->can('business_problems.manage');
    }
}
