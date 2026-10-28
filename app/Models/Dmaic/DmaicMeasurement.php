<?php

namespace App\Models\Dmaic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DmaicMeasurement extends Model
{
    use HasUuids;

    protected $fillable = [
        'dmaic_measurement_plan_id',
        'value',
        'measured_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'measured_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(DmaicMeasurementPlan::class, 'dmaic_measurement_plan_id');
    }
}
