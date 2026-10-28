<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Task extends Model
{
    use HasUuids;

    public const STATUSES = [
        'backlog',
        'ready',
        'in_progress',
        'code_review',
        'qa',
        'qa_failed',
        'ready_for_release',
        'released',
        'done',
        'blocked',
    ];

    /** @var list<array{id: string, label: string, statuses: list<string>}> */
    public const BOARD_COLUMNS = [
        ['id' => 'backlog', 'label' => 'Backlog', 'statuses' => ['backlog']],
        ['id' => 'ready', 'label' => 'Ready', 'statuses' => ['ready']],
        ['id' => 'in_progress', 'label' => 'In progress', 'statuses' => ['in_progress']],
        ['id' => 'review', 'label' => 'Review', 'statuses' => ['code_review']],
        ['id' => 'qa', 'label' => 'QA', 'statuses' => ['qa', 'qa_failed']],
        ['id' => 'blocked', 'label' => 'Blocked', 'statuses' => ['blocked']],
        ['id' => 'done', 'label' => 'Done', 'statuses' => ['ready_for_release', 'done', 'released']],
    ];

    public const BOARD_DONE_STATUSES = ['ready_for_release', 'done', 'released'];

    public const BOARD_DONE_DAYS = 14;

    public static function primaryStatusForColumn(string $columnId): ?string
    {
        foreach (self::BOARD_COLUMNS as $column) {
            if ($column['id'] === $columnId) {
                return $column['statuses'][0] ?? null;
            }
        }

        return null;
    }

    use LogsActivity;

    protected $fillable = [
        'delivery_project_id',
        'feature_id',
        'epic_id',
        'sprint_id',
        'parent_id',
        'title',
        'description',
        'assignee_id',
        'reviewer_id',
        'qa_id',
        'priority',
        'status',
        'start_date',
        'due_date',
        'estimate',
        'actual_effort',
        'completion_percent',
            'blockers',
        'git_reference',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'estimate' => 'decimal:2',
            'actual_effort' => 'decimal:2',
            'completion_percent' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function deliveryProject(): BelongsTo
    {
        return $this->belongsTo(DeliveryProject::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function epic(): BelongsTo
    {
        return $this->belongsTo(Epic::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function qa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qa_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'task_id',
            'depends_on_task_id'
        )->withTimestamps();
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'depends_on_task_id',
            'task_id'
        )->withTimestamps();
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && ! in_array($this->status, ['done', 'released'], true);
    }

    public function bugs(): HasMany
    {
        return $this->hasMany(Bug::class);
    }
}
