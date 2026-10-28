<?php

namespace App\Services;

use App\Models\DeliveryProject;
use App\Models\Epic;
use App\Models\Feature;
use App\Models\Task;
use Illuminate\Support\Carbon;

class ScheduleRollupService
{
    public function refreshFromTask(Task $task): void
    {
        if ($task->feature_id) {
            $this->refreshFeature($task->feature_id);
        }

        if ($task->epic_id) {
            $this->refreshEpic($task->epic_id);
        } elseif ($task->feature?->epic_id) {
            $this->refreshEpic($task->feature->epic_id);
        }

        if ($task->delivery_project_id) {
            $this->refreshProject($task->delivery_project_id);
        }
    }

    public function refreshFeature(string $featureId): void
    {
        $feature = Feature::query()->find($featureId);
        if (! $feature) {
            return;
        }

        $tasks = Task::query()
            ->where('feature_id', $feature->id)
            ->whereNull('parent_id')
            ->get(['start_date', 'due_date']);

        [$start, $end] = $this->envelopeFromTasks($tasks);

        $feature->forceFill([
            'rolled_start_date' => $start,
            'rolled_due_date' => $end,
        ])->saveQuietly();

        if ($feature->epic_id) {
            $this->refreshEpic($feature->epic_id);
        }

        if ($feature->delivery_project_id) {
            $this->refreshProject($feature->delivery_project_id);
        }
    }

    public function refreshEpic(string $epicId): void
    {
        $epic = Epic::query()->find($epicId);
        if (! $epic) {
            return;
        }

        $featureIds = Feature::query()->where('epic_id', $epic->id)->pluck('id');

        $tasks = Task::query()
            ->whereNull('parent_id')
            ->where(function ($q) use ($epic, $featureIds) {
                $q->where('epic_id', $epic->id);
                if ($featureIds->isNotEmpty()) {
                    $q->orWhereIn('feature_id', $featureIds);
                }
            })
            ->get(['start_date', 'due_date']);

        [$start, $end] = $this->envelopeFromTasks($tasks);

        // Also expand from feature rolled envelopes if present.
        $featureEnvelope = Feature::query()
            ->where('epic_id', $epic->id)
            ->where(function ($q) {
                $q->whereNotNull('rolled_start_date')->orWhereNotNull('rolled_due_date');
            })
            ->get(['rolled_start_date', 'rolled_due_date']);

        foreach ($featureEnvelope as $feature) {
            if ($feature->rolled_start_date) {
                $start = $start ? min($start, $feature->rolled_start_date->toDateString()) : $feature->rolled_start_date->toDateString();
            }
            if ($feature->rolled_due_date) {
                $end = $end ? max($end, $feature->rolled_due_date->toDateString()) : $feature->rolled_due_date->toDateString();
            }
        }

        $epic->forceFill([
            'rolled_start_date' => $start,
            'rolled_due_date' => $end,
        ])->saveQuietly();

        $projectIds = Feature::query()
            ->where('epic_id', $epic->id)
            ->whereNotNull('delivery_project_id')
            ->distinct()
            ->pluck('delivery_project_id');

        foreach ($projectIds as $projectId) {
            $this->refreshProject($projectId);
        }
    }

    public function refreshProject(?string $projectId): void
    {
        if (! $projectId) {
            return;
        }

        $project = DeliveryProject::query()->find($projectId);
        if (! $project) {
            return;
        }

        $tasks = Task::query()
            ->where('delivery_project_id', $project->id)
            ->whereNull('parent_id')
            ->get(['start_date', 'due_date']);

        [$start, $end] = $this->envelopeFromTasks($tasks);

        $project->forceFill([
            'rolled_start_date' => $start,
            'rolled_due_date' => $end,
        ])->saveQuietly();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Task>  $tasks
     * @return array{0: ?string, 1: ?string}
     */
    private function envelopeFromTasks($tasks): array
    {
        $starts = [];
        $ends = [];

        foreach ($tasks as $task) {
            if ($task->start_date) {
                $starts[] = Carbon::parse($task->start_date)->toDateString();
            }
            if ($task->due_date) {
                $ends[] = Carbon::parse($task->due_date)->toDateString();
            }
        }

        return [
            $starts === [] ? null : min($starts),
            $ends === [] ? null : max($ends),
        ];
    }

    public function plannedExceedsEnvelope(?string $plannedEnd, ?string $rolledEnd): bool
    {
        if (! $plannedEnd || ! $rolledEnd) {
            return false;
        }

        return Carbon::parse($rolledEnd)->gt(Carbon::parse($plannedEnd));
    }
}
