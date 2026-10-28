<?php

namespace App\Policies;

use App\Models\Dmaic\DmaicProfile;
use App\Models\User;

class DmaicProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('dmaic.view');
    }

    public function view(User $user, DmaicProfile $dmaicProfile): bool
    {
        return $user->can('dmaic.view');
    }

    public function create(User $user): bool
    {
        return $user->can('dmaic.manage');
    }

    public function update(User $user, DmaicProfile $dmaicProfile): bool
    {
        return $user->can('dmaic.manage');
    }

    public function approveGate(User $user, DmaicProfile $dmaicProfile): bool
    {
        return $user->can('dmaic.gate_approve');
    }
}
