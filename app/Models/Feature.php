<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Feature extends Model
{
    use HasUuids, LogsActivity;

    protected $fillable = [
        'system_id',
        'delivery_project_id',
        'epic_id',
        'title',
        'description',
        'acceptance_criteria',
        'dod_checklist',
        'priority',
        'status',
        'start_date',
        'due_date',
        'rolled_start_date',
        'rolled_due_date',
    ];

    public const DOD_KEYS = [
        'code_review' => 'Code reviewed',
        'qa' => 'QA completed',
        'ac_met' => 'Acceptance criteria satisfied',
        'docs' => 'Documentation updated',
        'po_accepted' => 'Product owner accepted',
        'released_if_applicable' => 'Released where applicable',
    ];

    protected function casts(): array
    {
        return [
            'dod_checklist' => 'array',
            'start_date' => 'date',
            'due_date' => 'date',
            'rolled_start_date' => 'date',
            'rolled_due_date' => 'date',
        ];
    }

    public function scheduleBeyondPlanned(): bool
    {
        if (! $this->due_date || ! $this->rolled_due_date) {
            return false;
        }

        return $this->rolled_due_date->gt($this->due_date);
    }

    public static function defaultDodChecklist(): array
    {
        return collect(self::DOD_KEYS)->map(fn ($label, $key) => [
            'key' => $key,
            'label' => $label,
            'completed' => false,
        ])->values()->all();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    public function deliveryProject(): BelongsTo
    {
        return $this->belongsTo(DeliveryProject::class);
    }

    public function epic(): BelongsTo
    {
        return $this->belongsTo(Epic::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function isBacklog(): bool
    {
        return $this->delivery_project_id === null;
    }
}
