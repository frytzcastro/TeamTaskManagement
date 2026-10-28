<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SupportTicket extends Model
{
    use HasUuids, LogsActivity;

    public const CATEGORIES = [
        'user_assistance',
        'account_issue',
        'payment_issue',
        'data_issue',
        'access_issue',
        'system_issue',
        'bug',
        'feature_request',
        'other',
    ];

    public const PRIORITIES = ['critical', 'high', 'medium', 'low'];

    public const STATUSES = [
        'open',
        'in_progress',
        'waiting',
        'resolved',
        'closed',
        'converted',
    ];

    protected $fillable = [
        'title',
        'description',
        'category',
        'priority',
        'status',
        'reporter_id',
        'assignee_id',
        'system_id',
        'delivery_project_id',
        'first_response_due_at',
        'resolve_due_at',
        'first_responded_at',
        'resolved_at',
        'converted_bug_id',
        'converted_task_id',
        'converted_feature_id',
    ];

    protected function casts(): array
    {
        return [
            'first_response_due_at' => 'datetime',
            'resolve_due_at' => 'datetime',
            'first_responded_at' => 'datetime',
            'resolved_at' => 'datetime',
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

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    public function deliveryProject(): BelongsTo
    {
        return $this->belongsTo(DeliveryProject::class);
    }

    public function convertedBug(): BelongsTo
    {
        return $this->belongsTo(Bug::class, 'converted_bug_id');
    }

    public function convertedTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'converted_task_id');
    }

    public function convertedFeature(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'converted_feature_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SupportComment::class);
    }

    public function isResponseBreached(): bool
    {
        return $this->first_responded_at === null
            && $this->first_response_due_at
            && $this->first_response_due_at->isPast()
            && ! in_array($this->status, ['resolved', 'closed', 'converted'], true);
    }

    public function isResolutionBreached(): bool
    {
        return $this->resolved_at === null
            && $this->resolve_due_at
            && $this->resolve_due_at->isPast()
            && ! in_array($this->status, ['resolved', 'closed', 'converted'], true);
    }

    public function applySlaFromPriority(): void
    {
        $sla = SupportSla::query()->where('priority', $this->priority)->first();
        if (! $sla) {
            return;
        }

        $base = $this->created_at ?? now();
        $this->first_response_due_at = $base->copy()->addMinutes($sla->response_minutes);
        $this->resolve_due_at = $base->copy()->addMinutes($sla->resolution_minutes);
    }

    public function applyStatusTimestamps(?string $previousStatus = null): void
    {
        if ($this->status === 'in_progress' && $this->first_responded_at === null) {
            $this->first_responded_at = now();
        }

        if (in_array($this->status, ['resolved', 'closed'], true) && $this->resolved_at === null) {
            $this->resolved_at = now();
            if ($this->first_responded_at === null) {
                $this->first_responded_at = now();
            }
        }

        if ($previousStatus && in_array($previousStatus, ['resolved', 'closed'], true)
            && ! in_array($this->status, ['resolved', 'closed', 'converted'], true)) {
            $this->resolved_at = null;
        }
    }
}
