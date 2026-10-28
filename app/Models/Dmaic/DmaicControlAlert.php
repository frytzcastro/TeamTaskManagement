<?php

namespace App\Models\Dmaic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DmaicControlAlert extends Model
{
    use HasUuids;

    protected $fillable = [
        'dmaic_control_metric_id',
        'severity',
        'message',
        'status',
        'triggered_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'triggered_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function metric(): BelongsTo
    {
        return $this->belongsTo(DmaicControlMetric::class, 'dmaic_control_metric_id');
    }
}
