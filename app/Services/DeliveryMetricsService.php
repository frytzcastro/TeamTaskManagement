<?php

namespace App\Services;

use App\Models\Bug;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DeliveryMetricsService
{
    /**
     * @return array{
     *   delivery: array<string, mixed>,
     *   quality: array<string, mixed>,
     *   filters: array<string, mixed>
     * }
     */
    public function summarize(
        ?Team $team = null,
        ?User $user = null,
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
    ): array {
        $from = $from ?? now()->subDays(30)->startOfDay();
        $to = $to ?? now()->endOfDay();

        $userIds = null;
        if ($user) {
            $userIds = [$user->id];
        } elseif ($team) {
            $userIds = $team->users()->pluck('users.id')->all();
        }

        $taskQuery = Task::query()->whereNull('parent_id');
        $this->scopeAssignees($taskQuery, 'assignee_id', $userIds);

        $assigned = (clone $taskQuery)->whereNotNull('assignee_id')->count();
        $completedQuery = (clone $taskQuery)->whereIn('status', ['done', 'released']);
        $completed = (clone $completedQuery)->count();
        $completedInPeriod = (clone $completedQuery)
            ->whereBetween('completed_at', [$from, $to])
            ->count();

        $overdue = (clone $taskQuery)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->whereNotIn('status', ['done', 'released'])
            ->count();

        $completedWithDue = (clone $completedQuery)->whereNotNull('due_date')->whereNotNull('completed_at');
        $completedWithDueCount = (clone $completedWithDue)->count();
        $onTime = (clone $completedWithDue)
            ->whereColumn('completed_at', '<=', 'due_date')
            ->count();

        $cycleTasks = (clone $completedQuery)
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->get(['started_at', 'completed_at']);

        $avgCycleHours = null;
        if ($cycleTasks->isNotEmpty()) {
            $avgCycleHours = round($cycleTasks->avg(function (Task $task) {
                return $task->started_at->diffInSeconds($task->completed_at) / 3600;
            }), 1);
        }

        $bugQuery = Bug::query();
        $this->scopeAssignees($bugQuery, 'assignee_id', $userIds);

        $bugsCreated = (clone $bugQuery)->whereBetween('created_at', [$from, $to])->count();
        $bugsResolved = (clone $bugQuery)
            ->whereIn('status', Bug::RESOLVED_STATUSES)
            ->where(function (Builder $q) use ($from, $to) {
                $q->whereBetween('resolved_at', [$from, $to])
                    ->orWhereBetween('closed_at', [$from, $to]);
            })
            ->count();

        $openCritical = (clone $bugQuery)
            ->where('severity', 'critical')
            ->whereNotIn('status', ['closed'])
            ->count();

        $resolvedOrClosed = (clone $bugQuery)->whereIn('status', Bug::RESOLVED_STATUSES)->count();
        $reopenedBugs = (clone $bugQuery)
            ->whereIn('status', Bug::RESOLVED_STATUSES)
            ->where('reopened_count', '>', 0)
            ->count();

        $productionBugs = (clone $bugQuery)->where('environment', 'production')->count();

        return [
            'delivery' => [
                'tasks_completed' => $completed,
                'tasks_completed_in_period' => $completedInPeriod,
                'tasks_overdue' => $overdue,
                'tasks_assigned' => $assigned,
                'completion_rate' => $assigned > 0 ? round($completed / $assigned * 100, 1) : null,
                'on_time_rate' => $completedWithDueCount > 0 ? round($onTime / $completedWithDueCount * 100, 1) : null,
                'avg_cycle_hours' => $avgCycleHours,
            ],
            'quality' => [
                'bugs_created' => $bugsCreated,
                'bugs_resolved' => $bugsResolved,
                'open_critical' => $openCritical,
                'reopen_rate' => $resolvedOrClosed > 0 ? round($reopenedBugs / $resolvedOrClosed * 100, 1) : null,
                'production_bugs' => $productionBugs,
            ],
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'team_id' => $team?->id,
                'user_id' => $user?->id,
            ],
        ];
    }

    /**
     * @param  list<int>|null  $userIds
     */
    private function scopeAssignees(Builder $query, string $column, ?array $userIds): void
    {
        if ($userIds === null) {
            return;
        }

        if ($userIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn($column, $userIds);
    }

    public function applyTaskTimestamps(Task $task, ?string $previousStatus = null): void
    {
        if ($task->status === 'in_progress' && ! $task->started_at) {
            $task->started_at = now();
        }

        if (in_array($task->status, ['done', 'released'], true)) {
            $task->completed_at ??= now();
            if (! $task->started_at) {
                $task->started_at = $task->start_date
                    ? Carbon::parse($task->start_date)->startOfDay()
                    : ($task->created_at ?? now());
            }
        } elseif ($previousStatus && in_array($previousStatus, ['done', 'released'], true)
            && ! in_array($task->status, ['done', 'released'], true)) {
            $task->completed_at = null;
        }
    }
}
