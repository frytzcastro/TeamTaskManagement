<?php

namespace App\Services;

use App\Models\DeliveryProject;
use App\Models\DeliveryProjectMilestone;
use App\Models\Epic;
use App\Models\Feature;
use App\Models\Release;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MilestoneSyncService
{
    public const SOURCE_EPIC = 'epic';

    public const SOURCE_RELEASE = 'release';

    public const SOURCE_MANUAL = 'manual';

    public function syncEpic(Epic $epic): void
    {
        $projectIds = Feature::query()
            ->where('epic_id', $epic->id)
            ->whereNotNull('delivery_project_id')
            ->distinct()
            ->pluck('delivery_project_id');

        DeliveryProjectMilestone::query()
            ->where('source_type', self::SOURCE_EPIC)
            ->where('source_id', $epic->id)
            ->whereNotIn('delivery_project_id', $projectIds)
            ->delete();

        if (! $epic->due_date || $projectIds->isEmpty()) {
            DeliveryProjectMilestone::query()
                ->where('source_type', self::SOURCE_EPIC)
                ->where('source_id', $epic->id)
                ->delete();

            return;
        }

        foreach ($projectIds as $projectId) {
            DeliveryProjectMilestone::query()->updateOrCreate(
                [
                    'delivery_project_id' => $projectId,
                    'source_type' => self::SOURCE_EPIC,
                    'source_id' => $epic->id,
                ],
                [
                    'title' => $epic->title,
                    'due_date' => $epic->due_date,
                    'status' => $this->epicDone($epic) ? 'done' : 'pending',
                ]
            );
        }
    }

    public function removeEpic(Epic $epic): void
    {
        DeliveryProjectMilestone::query()
            ->where('source_type', self::SOURCE_EPIC)
            ->where('source_id', $epic->id)
            ->delete();
    }

    public function syncRelease(Release $release): void
    {
        if (! $release->target_date) {
            $this->removeForSource($release->delivery_project_id, self::SOURCE_RELEASE, $release->id);

            return;
        }

        DeliveryProjectMilestone::query()->updateOrCreate(
            [
                'delivery_project_id' => $release->delivery_project_id,
                'source_type' => self::SOURCE_RELEASE,
                'source_id' => $release->id,
            ],
            [
                'title' => $release->name.($release->version ? ' '.$release->version : ''),
                'due_date' => $release->target_date,
                'status' => $release->status === 'deployed' ? 'done' : 'pending',
            ]
        );
    }

    public function removeRelease(Release $release): void
    {
        $this->removeForSource($release->delivery_project_id, self::SOURCE_RELEASE, $release->id);
    }

    public function reconcileProject(DeliveryProject $project): void
    {
        $project->loadMissing(['features.epic', 'releases']);

        $epics = $project->features
            ->pluck('epic')
            ->filter()
            ->unique('id')
            ->values();

        foreach ($epics as $epic) {
            $this->syncEpic($epic);
        }

        foreach ($project->releases as $release) {
            $this->syncRelease($release);
        }

        $this->pruneOrphanedAuto($project, $epics->pluck('id'));
    }

    /**
     * @return array{items: list<array<string, mixed>>, movement: array<string, mixed>}
     */
    public function expectationsFor(DeliveryProject $project): array
    {
        $this->reconcileProject($project);

        $project->unsetRelation('milestones');
        $project->load([
            'milestones',
            'features.epic',
            'features.tasks',
            'releases',
        ]);

        $epicsById = $project->features
            ->pluck('epic')
            ->filter()
            ->unique('id')
            ->keyBy('id');

        // Load epic features scoped to this project for progress
        foreach ($epicsById as $epic) {
            $epic->setRelation(
                'features',
                $project->features->where('epic_id', $epic->id)->values()
            );
            $epic->setRelation(
                'tasks',
                collect()
            );
        }

        $releasesById = $project->releases->keyBy('id');

        $items = $project->milestones
            ->sortBy(fn (DeliveryProjectMilestone $m) => $m->due_date?->timestamp ?? PHP_INT_MAX)
            ->values()
            ->map(function (DeliveryProjectMilestone $m) use ($epicsById, $releasesById) {
                return $this->enrichMilestone($m, $epicsById, $releasesById);
            })
            ->all();

        return [
            'items' => $items,
            'movement' => $this->movementSummary($items, $project),
        ];
    }

    /**
     * @param  Collection<string, Epic>  $epicsById
     * @param  Collection<string, Release>  $releasesById
     * @return array<string, mixed>
     */
    private function enrichMilestone(
        DeliveryProjectMilestone $milestone,
        Collection $epicsById,
        Collection $releasesById,
    ): array {
        $progress = 0;
        $health = 'on_track';
        $href = null;
        $sourceLabel = 'Custom';

        if ($milestone->source_type === self::SOURCE_EPIC && $milestone->source_id) {
            $epic = $epicsById->get($milestone->source_id);
            $sourceLabel = 'Epic';
            $href = $epic ? route('epics.show', $epic) : null;
            if ($epic) {
                $progress = $this->epicProgressPct($epic);
                $health = $this->epicHealth($epic, $progress);
            }
        } elseif ($milestone->source_type === self::SOURCE_RELEASE && $milestone->source_id) {
            $release = $releasesById->get($milestone->source_id);
            $sourceLabel = 'Release';
            $href = $release ? route('releases.show', $release) : null;
            if ($release) {
                $progress = $this->releaseProgressPct($release);
                $health = $this->releaseHealth($release);
            }
        } else {
            $health = $this->manualHealth($milestone);
            $progress = $milestone->status === 'done' ? 100 : 0;
        }

        return [
            'id' => $milestone->id,
            'title' => $milestone->title,
            'due_date' => $milestone->due_date?->toDateString(),
            'status' => $health === 'done' ? 'done' : ($milestone->status ?: 'pending'),
            'source_type' => $milestone->source_type ?: self::SOURCE_MANUAL,
            'source_id' => $milestone->source_id,
            'source_label' => $sourceLabel,
            'progress_pct' => $progress,
            'health' => $health,
            'href' => $href,
        ];
    }

    private function epicProgressPct(Epic $epic): int
    {
        $tasks = $epic->tasks
            ->where('parent_id', null)
            ->concat(
                $epic->features->flatMap(fn ($f) => $f->tasks->where('parent_id', null))
            )
            ->unique('id')
            ->values();

        if ($tasks->isNotEmpty()) {
            $done = $tasks->filter(fn (Task $t) => in_array($t->status, Task::BOARD_DONE_STATUSES, true))->count();

            return (int) round(($done / $tasks->count()) * 100);
        }

        $features = $epic->features;
        if ($features->isEmpty()) {
            return $this->epicDone($epic) ? 100 : 0;
        }

        $doneFeatures = $features->filter(fn ($f) => in_array($f->status, ['done', 'completed', 'released'], true))->count();

        return (int) round(($doneFeatures / $features->count()) * 100);
    }

    private function epicHealth(Epic $epic, int $progress): string
    {
        if ($this->epicDone($epic) || $progress >= 100) {
            return 'done';
        }

        $due = $epic->due_date;
        if (! $due) {
            return 'on_track';
        }

        $today = Carbon::today();
        if ($epic->scheduleBeyondPlanned() || ($due->lt($today) && $progress < 100)) {
            return 'slipped';
        }

        if ($due->lte($today->copy()->addDays(7)) && $progress < 80) {
            return 'at_risk';
        }

        return 'on_track';
    }

    private function epicDone(Epic $epic): bool
    {
        return in_array($epic->status, ['done', 'completed'], true);
    }

    private function releaseProgressPct(Release $release): int
    {
        return match ($release->status) {
            'deployed' => 100,
            'approved' => 75,
            'in_qa' => 50,
            'rolled_back' => 25,
            default => 0,
        };
    }

    private function releaseHealth(Release $release): string
    {
        if ($release->status === 'deployed') {
            return 'done';
        }

        if ($release->status === 'rolled_back') {
            return 'slipped';
        }

        $due = $release->target_date;
        if (! $due) {
            return 'on_track';
        }

        $today = Carbon::today();
        if ($due->lt($today)) {
            return 'slipped';
        }

        if ($due->lte($today->copy()->addDays(7)) && ! in_array($release->status, ['approved', 'deployed'], true)) {
            return 'at_risk';
        }

        return 'on_track';
    }

    private function manualHealth(DeliveryProjectMilestone $milestone): string
    {
        if ($milestone->status === 'done') {
            return 'done';
        }

        if ($milestone->due_date && $milestone->due_date->lt(Carbon::today())) {
            return 'slipped';
        }

        if ($milestone->due_date && $milestone->due_date->lte(Carbon::today()->addDays(7))) {
            return 'at_risk';
        }

        return 'on_track';
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function movementSummary(array $items, DeliveryProject $project): array
    {
        $counts = [
            'on_track' => 0,
            'at_risk' => 0,
            'slipped' => 0,
            'done' => 0,
        ];

        foreach ($items as $item) {
            $health = $item['health'] ?? 'on_track';
            if (isset($counts[$health])) {
                $counts[$health]++;
            }
        }

        $next = collect($items)
            ->filter(fn (array $i) => ($i['health'] ?? '') !== 'done')
            ->sortBy(fn (array $i) => $i['due_date'] ?? '9999-12-31')
            ->first();

        return [
            'counts' => $counts,
            'next' => $next ? [
                'title' => $next['title'],
                'due_date' => $next['due_date'],
                'health' => $next['health'],
                'href' => $next['href'],
            ] : null,
            'target_completion' => $project->target_completion?->toDateString(),
        ];
    }

    private function removeForSource(string $projectId, string $sourceType, string $sourceId): void
    {
        DeliveryProjectMilestone::query()
            ->where('delivery_project_id', $projectId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->delete();
    }

    /**
     * @param  Collection<int, string>|iterable<string>  $epicIds
     */
    private function pruneOrphanedAuto(DeliveryProject $project, $epicIds): void
    {
        $epicIdList = collect($epicIds)->values();
        $releaseIds = $project->releases->pluck('id');

        DeliveryProjectMilestone::query()
            ->where('delivery_project_id', $project->id)
            ->where('source_type', self::SOURCE_EPIC)
            ->whereNotNull('source_id')
            ->whereNotIn('source_id', $epicIdList)
            ->delete();

        DeliveryProjectMilestone::query()
            ->where('delivery_project_id', $project->id)
            ->where('source_type', self::SOURCE_RELEASE)
            ->whereNotNull('source_id')
            ->whereNotIn('source_id', $releaseIds)
            ->delete();
    }
}
