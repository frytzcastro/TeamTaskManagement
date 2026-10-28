<?php

namespace App\Services;

use App\Models\Bug;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DeveloperKpiService
{
    public const EARLY_CAP = 120.0;

    public const LATE_ZERO_WEEKS = 26;

    /**
     * @return array{
     *   owned_delivery: array<string, mixed>,
     *   commitment: array<string, mixed>,
     *   quality: array<string, mixed>,
     *   gaps: array{missing_estimate: int, missing_due_date: int},
     *   period: array{from: string, to: string}
     * }
     */
    public function summarize(User $user, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $from = ($from ?? now()->subDays(30)->startOfDay())->copy();
        $to = ($to ?? now()->endOfDay())->copy();

        $openAssigned = Task::query()
            ->where('assignee_id', $user->id)
            ->whereNull('parent_id')
            ->whereNotIn('status', ['done', 'released'])
            ->get(['id', 'estimate', 'due_date']);

        return [
            'owned_delivery' => $this->scoreOwnedDelivery($user, $from, $to),
            'commitment' => $this->scoreCommitment($user, $from, $to),
            'quality' => $this->scoreQuality($user, $from, $to),
            'gaps' => [
                'missing_estimate' => $openAssigned->filter(fn (Task $t) => $t->estimate === null || (float) $t->estimate <= 0)->count(),
                'missing_due_date' => $openAssigned->filter(fn (Task $t) => ! $t->due_date)->count(),
            ],
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ];
    }

    /**
     * @return array{score: ?float, scored_count: int, items: list<array<string, mixed>>}
     */
    public function scoreOwnedDelivery(User $user, CarbonInterface $from, CarbonInterface $to): array
    {
        $tasks = Task::query()
            ->where('assignee_id', $user->id)
            ->whereNull('parent_id')
            ->whereNotNull('due_date')
            ->where(function ($q) use ($from, $to) {
                $q->where(function ($inner) use ($from, $to) {
                    $inner->whereIn('status', ['done', 'released'])
                        ->whereBetween('completed_at', [$from, $to]);
                })->orWhere(function ($inner) {
                    $inner->whereNotIn('status', ['done', 'released'])
                        ->whereDate('due_date', '<', now());
                });
            })
            ->get();

        $items = [];
        foreach ($tasks as $task) {
            $score = $this->deliveryScoreForTask($task);
            if ($score === null) {
                continue;
            }
            $items[] = [
                'task_id' => $task->id,
                'title' => $task->title,
                'score' => $score,
                'due_date' => optional($task->due_date)?->toDateString(),
                'completed_at' => optional($task->completed_at)?->toDateString(),
                'status' => $task->status,
                'estimate' => $task->estimate,
            ];
        }

        return $this->aggregateScores($items);
    }

    /**
     * @return array{score: ?float, scored_count: int, items: list<array<string, mixed>>}
     */
    public function scoreCommitment(User $user, CarbonInterface $from, CarbonInterface $to): array
    {
        $tasks = Task::query()
            ->where('assignee_id', $user->id)
            ->whereNull('parent_id')
            ->whereIn('status', ['done', 'released'])
            ->whereBetween('completed_at', [$from, $to])
            ->whereNotNull('estimate')
            ->where('estimate', '>', 0)
            ->get();

        $items = [];
        foreach ($tasks as $task) {
            $actual = $this->actualWorkingDays($task);
            if ($actual === null || $actual <= 0) {
                continue;
            }
            $estimate = (float) $task->estimate;
            $score = min(self::EARLY_CAP, round(100 * $estimate / $actual, 1));
            $items[] = [
                'task_id' => $task->id,
                'title' => $task->title,
                'score' => $score,
                'estimate' => $estimate,
                'actual' => $actual,
            ];
        }

        return $this->aggregateScores($items);
    }

    /**
     * @return array{score: ?float, scored_count: int, resolved: int, reopened: int}
     */
    public function scoreQuality(User $user, CarbonInterface $from, CarbonInterface $to): array
    {
        $bugs = Bug::query()
            ->where('assignee_id', $user->id)
            ->whereIn('status', Bug::RESOLVED_STATUSES)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('resolved_at', [$from, $to])
                    ->orWhereBetween('closed_at', [$from, $to]);
            })
            ->get(['id', 'reopened_count']);

        $resolved = $bugs->count();
        if ($resolved === 0) {
            return [
                'score' => null,
                'scored_count' => 0,
                'resolved' => 0,
                'reopened' => 0,
            ];
        }

        $reopened = $bugs->filter(fn (Bug $b) => (int) $b->reopened_count > 0)->count();
        $score = max(0, round(100 * (1 - ($reopened / $resolved)), 1));

        return [
            'score' => $score,
            'scored_count' => $resolved,
            'resolved' => $resolved,
            'reopened' => $reopened,
        ];
    }

    public function deliveryScoreForTask(Task $task): ?float
    {
        if (! $task->due_date) {
            return null;
        }

        $due = Carbon::parse($task->due_date)->startOfDay();
        $finished = in_array($task->status, ['done', 'released'], true) && $task->completed_at
            ? Carbon::parse($task->completed_at)->startOfDay()
            : now()->startOfDay();

        // Positive = finished after due (late); negative = early.
        $signedDays = $due->diffInDays($finished, false);
        if ($signedDays === 0.0 || $signedDays === 0) {
            return 100.0;
        }

        $weeks = $signedDays / 7;
        $ptsPerWeek = 100 / self::LATE_ZERO_WEEKS;

        if ($weeks < 0) {
            $score = 100 + (abs($weeks) * $ptsPerWeek);

            return round(min(self::EARLY_CAP, $score), 1);
        }

        $score = 100 - ($weeks * $ptsPerWeek);

        return round(max(0, $score), 1);
    }

    public function actualWorkingDays(Task $task): ?float
    {
        if ($task->started_at && $task->completed_at) {
            return max(0.1, round($this->workingDaysBetween(
                Carbon::parse($task->started_at)->startOfDay(),
                Carbon::parse($task->completed_at)->startOfDay()
            ), 1));
        }

        if ($task->actual_effort !== null && (float) $task->actual_effort > 0) {
            return (float) $task->actual_effort;
        }

        return null;
    }

    public function workingDaysBetween(CarbonInterface $start, CarbonInterface $end): float
    {
        if ($end->lt($start)) {
            return 0.0;
        }

        $days = 0;
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if (! $cursor->isWeekend()) {
                $days++;
            }
            $cursor->addDay();
        }

        // Same-day start/complete counts as at least a fraction of a day
        return max(1, $days);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{score: ?float, scored_count: int, items: list<array<string, mixed>>}
     */
    private function aggregateScores(array $items): array
    {
        if ($items === []) {
            return ['score' => null, 'scored_count' => 0, 'items' => []];
        }

        $collection = Collection::make($items);
        $weighted = $collection->sum(function (array $item) {
            $weight = isset($item['estimate']) && (float) $item['estimate'] > 0
                ? (float) $item['estimate']
                : 1.0;

            return $item['score'] * $weight;
        });
        $weightSum = $collection->sum(function (array $item) {
            return isset($item['estimate']) && (float) $item['estimate'] > 0
                ? (float) $item['estimate']
                : 1.0;
        });

        return [
            'score' => round($weighted / max(0.1, $weightSum), 1),
            'scored_count' => count($items),
            'items' => $items,
        ];
    }
}
