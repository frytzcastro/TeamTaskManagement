<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Bug extends Model
{
    use HasUuids, LogsActivity;

    public const STATUSES = [
        'reported',
        'triaged',
        'assigned',
        'in_development',
        'ready_for_qa',
        'qa_testing',
        'resolved',
        'closed',
        'reopened',
    ];

    public const SEVERITIES = [
        'critical',
        'high',
        'medium',
        'low',
    ];

    public const ENVIRONMENTS = [
        'production',
        'staging',
        'development',
        'mobile',
        'web',
    ];

    public const RESOLVED_STATUSES = [
        'resolved',
        'closed',
    ];

    protected $fillable = [
        'title',
        'description',
        'steps_to_reproduce',
        'expected_result',
        'actual_result',
        'environment',
        'severity',
        'status',
        'reporter_id',
        'assignee_id',
        'delivery_project_id',
        'feature_id',
        'system_id',
        'task_id',
        'dmaic_profile_id',
        'dmaic_root_cause_id',
        'dmaic_improvement_id',
        'reopened_count',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'reopened_count' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function deliveryProject(): BelongsTo
    {
        return $this->belongsTo(DeliveryProject::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function dmaicProfile(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Dmaic\DmaicProfile::class);
    }

    public function dmaicRootCause(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Dmaic\DmaicRootCause::class);
    }

    public function dmaicImprovement(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Dmaic\DmaicImprovement::class);
    }

    public function applyStatusTimestamps(?string $previousStatus = null): void
    {
        if ($this->status === 'reopened' && $previousStatus !== 'reopened') {
            $this->reopened_count = ($this->reopened_count ?? 0) + 1;
            $this->resolved_at = null;
            $this->closed_at = null;
        }

        if ($this->status === 'resolved' && ! $this->resolved_at) {
            $this->resolved_at = now();
        }

        if ($this->status === 'closed') {
            $this->closed_at ??= now();
            $this->resolved_at ??= now();
        }
    }
}
