<?php

namespace App\Models\Dmaic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DmaicPhaseDeliverable extends Model
{
    use HasUuids, LogsActivity;

    protected $fillable = [
        'dmaic_phase_id',
        'key',
        'label',
        'required',
        'completed',
        'value',
        'evidence_url',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'completed' => 'boolean',
            'value' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(DmaicPhase::class, 'dmaic_phase_id');
    }
}
