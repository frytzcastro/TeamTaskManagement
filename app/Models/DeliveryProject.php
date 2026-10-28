<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use App\Models\Concerns\GeneratesCode;
use App\Models\Dmaic\DmaicProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DeliveryProject extends Model
{
    use GeneratesCode, HasUuids, LogsActivity;

    public const CODE_PREFIX = 'PRJ';

    protected $fillable = [
        'type',
        'initiative_id',
        'name',
        'project_owner_id',
        'product_owner_id',
        'team_id',
        'start_date',
        'target_completion',
        'actual_completion',
        'rolled_start_date',
        'rolled_due_date',
        'status',
        'health',
        'priority',
        'description',
        'success_criteria',
        'risks',
        'dependencies',
    ];

    public const TYPES = ['standard', 'dmaic'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'target_completion' => 'date',
            'actual_completion' => 'date',
            'rolled_start_date' => 'date',
            'rolled_due_date' => 'date',
        ];
    }

    public function scheduleBeyondPlanned(): bool
    {
        if (! $this->target_completion || ! $this->rolled_due_date) {
            return false;
        }

        return $this->rolled_due_date->gt($this->target_completion);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function initiative(): BelongsTo
    {
        return $this->belongsTo(Initiative::class);
    }

    public function projectOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_owner_id');
    }

    public function productOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'product_owner_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(DeliveryProjectMilestone::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'delivery_project_members')->withTimestamps();
    }

    public function features(): HasMany
    {
        return $this->hasMany(Feature::class);
    }

    /**
     * System epics that have at least one feature assigned to this project.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Epic>
     */
    public function planEpicsQuery()
    {
        return Epic::query()
            ->whereIn('id', $this->features()->whereNotNull('epic_id')->select('epic_id'))
            ->orderBy('title');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function kpis(): BelongsToMany
    {
        return $this->belongsToMany(Kpi::class, 'delivery_project_kpi')->withTimestamps();
    }

    public function bugs(): HasMany
    {
        return $this->hasMany(Bug::class);
    }

    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(Release::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function dmaicProfile(): HasOne
    {
        return $this->hasOne(DmaicProfile::class);
    }

    public function isDmaic(): bool
    {
        return $this->type === 'dmaic';
    }
}
