<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiMeasurement extends Model
{
    use HasUuids;

    protected $fillable = [
        'kpi_id',
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

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(Kpi::class);
    }
}
