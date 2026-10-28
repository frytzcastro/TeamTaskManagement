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

class Kpi extends Model
{
    use GeneratesCode, HasUuids, LogsActivity;

    public const CODE_PREFIX = 'KPI';

    protected $fillable = [
        'metric_layer',
        'name',
        'definition',
        'formula',
        'owner_id',
        'data_source',
        'baseline',
        'target',
        'current_value',
        'reporting_period',
        'direction',
        'status',
        'trend',
        'unit',
    ];

    public const METRIC_LAYERS = ['business', 'process', 'six_sigma'];

    protected function casts(): array
    {
        return [
            'baseline' => 'decimal:4',
            'target' => 'decimal:4',
            'current_value' => 'decimal:4',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function initiatives(): BelongsToMany
    {
        return $this->belongsToMany(Initiative::class, 'initiative_kpi')->withTimestamps();
    }

    public function deliveryProjects(): BelongsToMany
    {
        return $this->belongsToMany(DeliveryProject::class, 'delivery_project_kpi')->withTimestamps();
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(KpiMeasurement::class);
    }
}
