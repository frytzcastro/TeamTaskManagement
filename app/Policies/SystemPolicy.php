<?php

namespace App\Policies;

use App\Models\User;

class SystemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('systems.view');
    }

    public function view(User $user): bool
    {
        return $user->can('systems.view');
    }

    public function create(User $user): bool
    {
        return $user->can('systems.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('systems.manage');
    }

    public function delete(User $user): bool
    {
        return $user->can('systems.manage');
    }
}
