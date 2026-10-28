<?php

namespace App\Models\Dmaic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DmaicMeasurementPlan extends Model
{
    use HasUuids, LogsActivity;

    protected $fillable = [
        'dmaic_profile_id',
        'metric_name',
        'definition',
        'formula',
        'data_source',
        'frequency',
        'data_owner_id',
        'collection_method',
        'sample_size',
        'data_quality_status',
        'baseline_value',
    ];

    protected function casts(): array
    {
        return [
            'baseline_value' => 'decimal:4',
            'sample_size' => 'integer',
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

    public function dataOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'data_owner_id');
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(DmaicMeasurement::class);
    }
}
