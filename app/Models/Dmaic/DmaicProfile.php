<?php

namespace App\Models\Dmaic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use App\Models\BusinessProblem;
use App\Models\DeliveryProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DmaicProfile extends Model
{
    use HasUuids, LogsActivity;

    public const PHASES = ['DEFINE', 'MEASURE', 'ANALYZE', 'IMPROVE', 'CONTROL', 'CLOSED'];

    protected $fillable = [
        'delivery_project_id',
        'business_problem_id',
        'current_phase',
        'status',
        'sponsor_id',
        'leader_id',
        'process_owner_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function deliveryProject(): BelongsTo
    {
        return $this->belongsTo(DeliveryProject::class);
    }

    public function businessProblem(): BelongsTo
    {
        return $this->belongsTo(BusinessProblem::class);
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function processOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'process_owner_id');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(DmaicPhase::class);
    }

    public function measurementPlans(): HasMany
    {
        return $this->hasMany(DmaicMeasurementPlan::class);
    }

    public function rootCauses(): HasMany
    {
        return $this->hasMany(DmaicRootCause::class);
    }

    public function improvements(): HasMany
    {
        return $this->hasMany(DmaicImprovement::class);
    }

    public function controlPlans(): HasMany
    {
        return $this->hasMany(DmaicControlPlan::class);
    }

    public function currentPhaseRecord(): ?DmaicPhase
    {
        return $this->phases->firstWhere('phase', $this->current_phase);
    }
}
