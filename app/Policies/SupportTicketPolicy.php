<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('support.view');
    }

    public function view(User $user, SupportTicket $supportTicket): bool
    {
        return $user->can('support.view');
    }

    public function create(User $user): bool
    {
        return $user->can('support.manage') || $user->can('support.update');
    }

    public function update(User $user, SupportTicket $supportTicket): bool
    {
        if ($user->can('support.manage')) {
            return true;
        }

        return $user->can('support.update')
            && ($supportTicket->assignee_id === $user->id || $supportTicket->reporter_id === $user->id);
    }

    public function delete(User $user, SupportTicket $supportTicket): bool
    {
        return $user->can('support.manage');
    }

    public function convert(User $user, SupportTicket $supportTicket): bool
    {
        return $user->can('support.convert') || $user->can('support.manage');
    }
}
