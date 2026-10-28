<?php

namespace App\Models\Dmaic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use App\Models\Kpi;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DmaicControlMetric extends Model
{
    use HasUuids, LogsActivity;

    protected $fillable = [
        'dmaic_control_plan_id',
        'kpi_id',
        'metric_name',
        'baseline',
        'target',
        'current_value',
        'frequency',
        'owner_id',
        'data_source',
        'ucl',
        'lcl',
    ];

    protected function casts(): array
    {
        return [
            'baseline' => 'decimal:4',
            'target' => 'decimal:4',
            'current_value' => 'decimal:4',
            'ucl' => 'decimal:4',
            'lcl' => 'decimal:4',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function controlPlan(): BelongsTo
    {
        return $this->belongsTo(DmaicControlPlan::class, 'dmaic_control_plan_id');
    }

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(Kpi::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(DmaicControlAlert::class);
    }

    public function isBreached(): bool
    {
        if ($this->current_value === null) {
            return false;
        }

        if ($this->ucl !== null && $this->current_value > $this->ucl) {
            return true;
        }

        if ($this->lcl !== null && $this->current_value < $this->lcl) {
            return true;
        }

        return false;
    }
}
