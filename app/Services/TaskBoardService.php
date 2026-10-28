<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Collection;

class TaskBoardService
{
    /**
     * @param  Collection<int, Task>  $tasks
     * @return list<array{id: string, label: string, statuses: list<string>, tasks: list<Task>}>
     */
    public function groupIntoColumns(Collection $tasks): array
    {
        return collect(Task::BOARD_COLUMNS)->map(function (array $column) use ($tasks) {
            return [
                'id' => $column['id'],
                'label' => $column['label'],
                'statuses' => $column['statuses'],
                'tasks' => $tasks
                    ->filter(fn (Task $t) => in_array($t->status, $column['statuses'], true))
                    ->values()
                    ->all(),
            ];
        })->all();
    }

    /**
     * @param  list<string>|null  $assigneeIds
     * @return Collection<int, Task>
     */
    public function loadBoardTasks(?array $assigneeIds = null, ?string $projectId = null): Collection
    {
        $open = Task::query()
            ->whereNull('parent_id')
            ->whereNotIn('status', ['done', 'released'])
            ->when($assigneeIds !== null, function ($q) use ($assigneeIds) {
                if ($assigneeIds === []) {
                    $q->whereRaw('1 = 0');
                } else {
                    $q->whereIn('assignee_id', $assigneeIds);
                }
            })
            ->when($projectId, fn ($q) => $q->where('delivery_project_id', $projectId))
            ->with(['deliveryProject', 'assignee'])
            ->orderBy('due_date')
            ->get();

        $recentDone = Task::query()
            ->whereNull('parent_id')
            ->whereIn('status', Task::BOARD_DONE_STATUSES)
            ->when($assigneeIds !== null, function ($q) use ($assigneeIds) {
                if ($assigneeIds === []) {
                    $q->whereRaw('1 = 0');
                } else {
                    $q->whereIn('assignee_id', $assigneeIds);
                }
            })
            ->when($projectId, fn ($q) => $q->where('delivery_project_id', $projectId))
            ->where(function ($q) {
                $q->where('completed_at', '>=', now()->subDays(Task::BOARD_DONE_DAYS))
                    ->orWhere(function ($inner) {
                        $inner->whereNull('completed_at')
                            ->where('status', 'ready_for_release')
                            ->where('updated_at', '>=', now()->subDays(Task::BOARD_DONE_DAYS));
                    });
            })
            ->with(['deliveryProject', 'assignee'])
            ->orderByDesc('completed_at')
            ->get();

        return $open->merge($recentDone)->unique('id')->values();
    }

    /**
     * @param  Collection<int, Task>  $openTasks
     * @return array{
     *   overdue: list<Task>,
     *   blocked: list<Task>,
     *   missing_estimate: list<Task>,
     *   high_wip_assignees: list<array{assignee_id: ?string, name: string, count: int}>
     * }
     */
    public function attention(Collection $openTasks, int $wipLimit = 2): array
    {
        $overdue = $openTasks->filter(fn (Task $t) => $t->isOverdue())->values();
        $blocked = $openTasks->filter(
            fn (Task $t) => $t->status === 'blocked' || filled($t->blockers)
        )->values();
        $missingEstimate = $openTasks
            ->filter(fn (Task $t) => in_array($t->status, ['ready', 'in_progress'], true))
            ->filter(fn (Task $t) => $t->estimate === null || (float) $t->estimate <= 0)
            ->values();

        $highWip = $openTasks
            ->where('status', 'in_progress')
            ->groupBy(fn (Task $t) => $t->assignee_id ?? 'unassigned')
            ->map(function (Collection $group, $key) {
                return [
                    'assignee_id' => $key === 'unassigned' ? null : (string) $key,
                    'name' => $group->first()?->assignee?->name ?? 'Unassigned',
                    'count' => $group->count(),
                ];
            })
            ->filter(fn (array $row) => $row['count'] > $wipLimit)
            ->values()
            ->all();

        return [
            'overdue' => $overdue->take(15)->all(),
            'blocked' => $blocked->take(15)->all(),
            'missing_estimate' => $missingEstimate->take(15)->all(),
            'high_wip_assignees' => $highWip,
        ];
    }
}
