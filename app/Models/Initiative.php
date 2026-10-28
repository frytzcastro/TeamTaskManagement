<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use App\Models\Concerns\GeneratesCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Initiative extends Model
{
    use GeneratesCode, HasUuids, LogsActivity;

    public const CODE_PREFIX = 'INIT';

    protected $fillable = [
        'strategic_pillar_id',
        'name',
        'owner_id',
        'start_date',
        'target_date',
        'priority',
        'status',
        'business_goal',
        'problem',
        'opportunity',
        'expected_business_impact',
        'success_criteria',
        'dependencies',
        'risks',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'target_date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function strategicPillar(): BelongsTo
    {
        return $this->belongsTo(StrategicPillar::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function deliveryProjects(): HasMany
    {
        return $this->hasMany(DeliveryProject::class);
    }

    public function businessProblems(): HasMany
    {
        return $this->hasMany(BusinessProblem::class);
    }

    public function kpis(): BelongsToMany
    {
        return $this->belongsToMany(Kpi::class, 'initiative_kpi')->withTimestamps();
    }

    public function systems(): BelongsToMany
    {
        return $this->belongsToMany(System::class, 'initiative_system')->withTimestamps();
    }
}
