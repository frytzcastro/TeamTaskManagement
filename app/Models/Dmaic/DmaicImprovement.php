<?php

namespace App\Models\Dmaic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use App\Models\Feature;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DmaicImprovement extends Model
{
    use HasUuids, LogsActivity;

    protected $fillable = [
        'dmaic_profile_id',
        'dmaic_root_cause_id',
        'feature_id',
        'title',
        'description',
        'owner_id',
        'expected_impact',
        'expected_cost',
        'expected_benefit',
        'status',
        'is_pilot',
        'before_measurement',
        'expected_measurement',
        'after_measurement',
    ];

    protected function casts(): array
    {
        return [
            'is_pilot' => 'boolean',
            'expected_cost' => 'decimal:2',
            'expected_benefit' => 'decimal:2',
            'before_measurement' => 'decimal:4',
            'expected_measurement' => 'decimal:4',
            'after_measurement' => 'decimal:4',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(DmaicProfile::class, 'dmaic_profile_id');
    }

    public function rootCause(): BelongsTo
    {
        return $this->belongsTo(DmaicRootCause::class, 'dmaic_root_cause_id');
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
