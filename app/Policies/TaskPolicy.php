<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tasks.view');
    }

    public function view(User $user): bool
    {
        return $user->can('tasks.view');
    }

    public function create(User $user): bool
    {
        return $user->can('tasks.manage');
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->can('tasks.manage')) {
            return true;
        }

        return $user->can('tasks.update')
            && ($task->assignee_id === $user->id
                || $task->reviewer_id === $user->id
                || $task->qa_id === $user->id);
    }

    public function delete(User $user): bool
    {
        return $user->can('tasks.manage');
    }
}
