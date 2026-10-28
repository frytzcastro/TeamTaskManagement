<?php

namespace App\Services;

use App\Models\DeliveryProject;
use App\Models\Epic;
use App\Models\Feature;
use App\Models\Task;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ScheduleTimelineService
{
    public function __construct(private ScheduleRollupService $rollup) {}

    /**
     * @param  array{project_id?: ?string, assignee_id?: ?string}  $filters
     * @return array{
     *   bars: list<array<string, mixed>>,
     *   undated: list<array<string, mixed>>,
     *   conflicts: array{slip_count: int, overlap_count: int},
     *   window: array{from: string, to: string},
     *   filters: array{project_id: ?string, assignee_id: ?string, from: ?string, to: ?string}
     * }
     */
    public function portfolio(?CarbonInterface $from = null, ?CarbonInterface $to = null, array $filters = []): array
    {
        $projectId = $filters['project_id'] ?? null;
        $assigneeId = $filters['assignee_id'] ?? null;

        $projects = DeliveryProject::query()
            ->with('initiative')
            ->when($projectId, fn ($q) => $q->where('id', $projectId))
            ->when($assigneeId && ! $projectId, function ($q) use ($assigneeId) {
                $q->whereIn('id', Task::query()
                    ->where('assignee_id', $assigneeId)
                    ->whereNull('parent_id')
                    ->select('delivery_project_id'));
            })
            ->orderBy('name')
            ->get();

        $bars = [];
        $undated = [];
        $slipCount = 0;
        $overlapCount = 0;

        foreach ($projects as $project) {
            $this->rollup->refreshProject($project->id);
            $project->refresh();

            if ($projectId && (string) $project->id === (string) $projectId) {
                $tree = $this->forProject($project, $assigneeId);
                $bars = array_merge($bars, $tree['bars']);
                $undated = array_merge($undated, $tree['undated']);
                $slipCount += $tree['conflicts']['slip_count'];
                $overlapCount += $tree['conflicts']['overlap_count'];
                continue;
            }

            $node = $this->projectBar($project);
            if ($this->isUndated($node)) {
                $undated[] = $this->undatedEntry($node);
                continue;
            }

            if ($from && $to && ! $this->overlapsWindow($node, $from, $to)) {
                continue;
            }

            $openTasks = Task::query()
                ->where('delivery_project_id', $project->id)
                ->whereNull('parent_id')
                ->whereNotIn('status', ['done', 'released'])
                ->get(['id', 'assignee_id', 'start_date', 'due_date', 'status', 'title']);

            $overlaps = $this->detectOverlaps($openTasks);
            $projectOverlaps = $overlaps->flatten()->unique()->count();
            if ($projectOverlaps > 0) {
                $node['overlap'] = true;
                $node['overlap_task_ids'] = $overlaps->flatten()->unique()->values()->all();
                $overlapCount += (int) floor($projectOverlaps / 2);
            }

            if ($node['slip'] || $node['overdue']) {
                $slipCount++;
            }

            $bars[] = $node;
        }

        $window = $this->computeWindow($bars, $from, $to);

        return [
            'bars' => $bars,
            'undated' => $undated,
            'conflicts' => [
                'slip_count' => $slipCount,
                'overlap_count' => $overlapCount,
            ],
            'window' => $window,
            'filters' => [
                'project_id' => $projectId,
                'assignee_id' => $assigneeId,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
        ];
    }

    /**
     * @return array{
     *   bars: list<array<string, mixed>>,
     *   undated: list<array<string, mixed>>,
     *   conflicts: array{slip_count: int, overlap_count: int},
     *   window: array{from: string, to: string}
     * }
     */
    public function forProject(DeliveryProject $project, ?string $assigneeId = null): array
    {
        foreach (Feature::query()->where('delivery_project_id', $project->id)->pluck('id') as $featureId) {
            $this->rollup->refreshFeature($featureId);
        }
        $epicIds = Feature::query()
            ->where('delivery_project_id', $project->id)
            ->whereNotNull('epic_id')
            ->distinct()
            ->pluck('epic_id');
        foreach ($epicIds as $epicId) {
            $this->rollup->refreshEpic($epicId);
        }
        $this->rollup->refreshProject($project->id);

        // Work on a fresh instance so callers (e.g. hub show) keep their eager-loaded context.
        $project = DeliveryProject::query()->findOrFail($project->id);

        $project->load([
            'features' => fn ($q) => $q->orderBy('title'),
            'tasks' => fn ($q) => $q->whereNull('parent_id')->with('assignee')->orderBy('title'),
        ]);
        $planEpics = $project->planEpicsQuery()->get();

        $tasks = $project->tasks;
        if ($assigneeId) {
            $tasks = $tasks->where('assignee_id', $assigneeId)->values();
        }

        $overlaps = $this->detectOverlaps(
            $tasks->whereNotIn('status', ['done', 'released'])->values()
        );

        $bars = [];
        $undated = [];

        $projectNode = $this->projectBar($project);
        $this->pushNode($projectNode, $bars, $undated);

        $featuresByEpic = $project->features->groupBy('epic_id');
        $orphanFeatures = $project->features->whereNull('epic_id');

        foreach ($planEpics as $epic) {
            $epicNode = $this->epicBar($epic, $project->id);
            $this->pushNode($epicNode, $bars, $undated);

            foreach ($featuresByEpic->get($epic->id, collect()) as $feature) {
                $featureNode = $this->featureBar($feature, $epic->id);
                $this->pushNode($featureNode, $bars, $undated);

                foreach ($tasks->where('feature_id', $feature->id) as $task) {
                    $taskNode = $this->taskBar($task, $feature->id, $overlaps);
                    $this->pushNode($taskNode, $bars, $undated);
                }
            }

            foreach ($tasks->where('epic_id', $epic->id)->whereNull('feature_id') as $task) {
                $taskNode = $this->taskBar($task, $epic->id, $overlaps);
                $this->pushNode($taskNode, $bars, $undated);
            }
        }

        foreach ($orphanFeatures as $feature) {
            $featureNode = $this->featureBar($feature, $project->id);
            $this->pushNode($featureNode, $bars, $undated);

            foreach ($tasks->where('feature_id', $feature->id) as $task) {
                $taskNode = $this->taskBar($task, $feature->id, $overlaps);
                $this->pushNode($taskNode, $bars, $undated);
            }
        }

        foreach ($tasks->whereNull('feature_id')->whereNull('epic_id') as $task) {
            $taskNode = $this->taskBar($task, $project->id, $overlaps);
            $this->pushNode($taskNode, $bars, $undated);
        }

        $slipCount = collect($bars)->filter(fn ($b) => ($b['slip'] ?? false) || ($b['overdue'] ?? false))->count();
        $overlapCount = (int) floor(collect($bars)->filter(fn ($b) => $b['overlap'] ?? false)->count() / 2);

        return [
            'bars' => $bars,
            'undated' => $undated,
            'conflicts' => [
                'slip_count' => $slipCount,
                'overlap_count' => $overlapCount,
            ],
            'window' => $this->computeWindow($bars, null, null),
        ];
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @return Collection<string, Collection<int, string>> map task_id => overlapping task ids
     */
    public function detectOverlaps(Collection $tasks): Collection
    {
        $byAssignee = $tasks
            ->filter(fn (Task $t) => $t->assignee_id && $this->taskWindowStart($t) && $this->taskWindowEnd($t))
            ->groupBy('assignee_id');

        $map = collect();

        foreach ($byAssignee as $group) {
            $list = $group->values();
            for ($i = 0; $i < $list->count(); $i++) {
                for ($j = $i + 1; $j < $list->count(); $j++) {
                    $a = $list[$i];
                    $b = $list[$j];
                    if ($this->datesOverlap(
                        $this->taskWindowStart($a),
                        $this->taskWindowEnd($a),
                        $this->taskWindowStart($b),
                        $this->taskWindowEnd($b),
                    )) {
                        $map[$a->id] = ($map[$a->id] ?? collect())->push($b->id)->unique()->values();
                        $map[$b->id] = ($map[$b->id] ?? collect())->push($a->id)->unique()->values();
                    }
                }
            }
        }

        return $map;
    }

    public function datesOverlap(?string $aStart, ?string $aEnd, ?string $bStart, ?string $bEnd): bool
    {
        if (! $aStart || ! $aEnd || ! $bStart || ! $bEnd) {
            return false;
        }

        $aS = Carbon::parse($aStart)->startOfDay();
        $aE = Carbon::parse($aEnd)->startOfDay();
        $bS = Carbon::parse($bStart)->startOfDay();
        $bE = Carbon::parse($bEnd)->startOfDay();

        // Inclusive overlap: adjacent end==next start does NOT overlap if end < start of other
        return $aS->lte($bE) && $bS->lte($aE);
    }

    public function hasSlip(?string $plannedEnd, ?string $rolledEnd): bool
    {
        if (! $plannedEnd || ! $rolledEnd) {
            return false;
        }

        return Carbon::parse($rolledEnd)->startOfDay()->gt(Carbon::parse($plannedEnd)->startOfDay());
    }

    /**
     * @param  list<array<string, mixed>>  $bars
     * @param  list<array<string, mixed>>  $undated
     */
    private function pushNode(array $node, array &$bars, array &$undated): void
    {
        if ($this->isUndated($node)) {
            $undated[] = $this->undatedEntry($node);

            return;
        }

        $bars[] = $node;
    }

    /**
     * @return array<string, mixed>
     */
    private function projectBar(DeliveryProject $project): array
    {
        $plannedStart = optional($project->start_date)?->toDateString();
        $plannedEnd = optional($project->target_completion)?->toDateString();
        $rolledStart = optional($project->rolled_start_date)?->toDateString();
        $rolledEnd = optional($project->rolled_due_date)?->toDateString();
        $slip = $this->hasSlip($plannedEnd, $rolledEnd);

        return $this->baseNode(
            id: 'project:'.$project->id,
            entityId: $project->id,
            type: 'project',
            title: $project->name,
            parentId: null,
            plannedStart: $plannedStart,
            plannedEnd: $plannedEnd,
            rolledStart: $rolledStart,
            rolledEnd: $rolledEnd,
            status: $project->status,
            slip: $slip,
            href: route('delivery-projects.show', ['deliveryProject' => $project->id, 'tab' => 'schedule']),
            depth: 0,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function epicBar(Epic $epic, string $parentId): array
    {
        $plannedStart = optional($epic->start_date)?->toDateString();
        $plannedEnd = optional($epic->due_date)?->toDateString();
        $rolledStart = optional($epic->rolled_start_date)?->toDateString();
        $rolledEnd = optional($epic->rolled_due_date)?->toDateString();

        return $this->baseNode(
            id: 'epic:'.$epic->id,
            entityId: $epic->id,
            type: 'epic',
            title: $epic->title,
            parentId: 'project:'.$parentId,
            plannedStart: $plannedStart,
            plannedEnd: $plannedEnd,
            rolledStart: $rolledStart,
            rolledEnd: $rolledEnd,
            status: $epic->status,
            slip: $this->hasSlip($plannedEnd, $rolledEnd),
            href: route('epics.show', $epic),
            depth: 1,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function featureBar(Feature $feature, string $parentEntityId): array
    {
        $plannedStart = optional($feature->start_date)?->toDateString();
        $plannedEnd = optional($feature->due_date)?->toDateString();
        $rolledStart = optional($feature->rolled_start_date)?->toDateString();
        $rolledEnd = optional($feature->rolled_due_date)?->toDateString();
        $parentId = $feature->epic_id
            ? 'epic:'.$feature->epic_id
            : 'project:'.$parentEntityId;

        return $this->baseNode(
            id: 'feature:'.$feature->id,
            entityId: $feature->id,
            type: 'feature',
            title: $feature->title,
            parentId: $parentId,
            plannedStart: $plannedStart,
            plannedEnd: $plannedEnd,
            rolledStart: $rolledStart,
            rolledEnd: $rolledEnd,
            status: $feature->status,
            slip: $this->hasSlip($plannedEnd, $rolledEnd),
            href: route('features.show', $feature),
            depth: 2,
        );
    }

    /**
     * @param  Collection<string, Collection<int, string>>  $overlaps
     * @return array<string, mixed>
     */
    private function taskBar(Task $task, string $parentEntityId, Collection $overlaps): array
    {
        $plannedStart = optional($task->start_date)?->toDateString();
        $plannedEnd = optional($task->due_date)?->toDateString();
        $overdue = $task->isOverdue();
        $completedLate = in_array($task->status, ['done', 'released'], true)
            && $task->due_date
            && $task->completed_at
            && Carbon::parse($task->completed_at)->startOfDay()->gt(Carbon::parse($task->due_date)->startOfDay());
        $slip = $overdue || $completedLate;
        $overlapIds = ($overlaps[$task->id] ?? collect())->values()->all();

        $parentId = $task->feature_id
            ? 'feature:'.$task->feature_id
            : ($task->epic_id ? 'epic:'.$task->epic_id : 'project:'.$task->delivery_project_id);

        $node = $this->baseNode(
            id: 'task:'.$task->id,
            entityId: $task->id,
            type: 'task',
            title: $task->title,
            parentId: $parentId,
            plannedStart: $plannedStart,
            plannedEnd: $plannedEnd,
            rolledStart: null,
            rolledEnd: null,
            status: $task->status,
            slip: $slip,
            href: route('tasks.show', $task),
            depth: 3,
        );
        $node['assignee_id'] = $task->assignee_id;
        $node['assignee_name'] = $task->assignee?->name;
        $node['overdue'] = $overdue;
        $node['overlap'] = $overlapIds !== [];
        $node['overlap_task_ids'] = $overlapIds;

        return $node;
    }

    /**
     * @return array<string, mixed>
     */
    private function baseNode(
        string $id,
        string $entityId,
        string $type,
        string $title,
        ?string $parentId,
        ?string $plannedStart,
        ?string $plannedEnd,
        ?string $rolledStart,
        ?string $rolledEnd,
        ?string $status,
        bool $slip,
        string $href,
        int $depth,
    ): array {
        $displayStart = $rolledStart ?: $plannedStart;
        $displayEnd = $rolledEnd ?: $plannedEnd;
        if ($displayStart && ! $displayEnd) {
            $displayEnd = $displayStart;
        }
        if ($displayEnd && ! $displayStart) {
            $displayStart = $displayEnd;
        }

        return [
            'id' => $id,
            'entity_id' => $entityId,
            'type' => $type,
            'title' => $title,
            'parent_id' => $parentId,
            'planned_start' => $plannedStart,
            'planned_end' => $plannedEnd,
            'rolled_start' => $rolledStart,
            'rolled_end' => $rolledEnd,
            'display_start' => $displayStart,
            'display_end' => $displayEnd,
            'status' => $status,
            'assignee_id' => null,
            'assignee_name' => null,
            'slip' => $slip,
            'overdue' => false,
            'overlap' => false,
            'overlap_task_ids' => [],
            'href' => $href,
            'depth' => $depth,
        ];
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function isUndated(array $node): bool
    {
        return empty($node['display_start']) || empty($node['display_end']);
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function undatedEntry(array $node): array
    {
        return [
            'id' => $node['id'],
            'entity_id' => $node['entity_id'],
            'type' => $node['type'],
            'title' => $node['title'],
            'status' => $node['status'],
            'href' => $node['href'],
            'assignee_name' => $node['assignee_name'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function overlapsWindow(array $node, CarbonInterface $from, CarbonInterface $to): bool
    {
        return $this->datesOverlap(
            $node['display_start'],
            $node['display_end'],
            $from->toDateString(),
            $to->toDateString(),
        );
    }

    private function taskWindowStart(Task $task): ?string
    {
        return optional($task->start_date)?->toDateString() ?: optional($task->due_date)?->toDateString();
    }

    private function taskWindowEnd(Task $task): ?string
    {
        return optional($task->due_date)?->toDateString() ?: optional($task->start_date)?->toDateString();
    }

    /**
     * @param  list<array<string, mixed>>  $bars
     * @return array{from: string, to: string}
     */
    private function computeWindow(array $bars, ?CarbonInterface $from, ?CarbonInterface $to): array
    {
        if ($from && $to) {
            return ['from' => $from->toDateString(), 'to' => $to->toDateString()];
        }

        $starts = [];
        $ends = [];
        foreach ($bars as $bar) {
            if (! empty($bar['display_start'])) {
                $starts[] = $bar['display_start'];
            }
            if (! empty($bar['display_end'])) {
                $ends[] = $bar['display_end'];
            }
        }

        if ($starts === [] || $ends === []) {
            return [
                'from' => now()->subMonths(3)->toDateString(),
                'to' => now()->addMonths(3)->toDateString(),
            ];
        }

        return [
            'from' => Carbon::parse(min($starts))->subWeeks(2)->toDateString(),
            'to' => Carbon::parse(max($ends))->addWeeks(2)->toDateString(),
        ];
    }
}
