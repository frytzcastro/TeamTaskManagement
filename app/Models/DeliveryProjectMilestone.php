<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DeliveryProjectMilestone extends Model
{
    use HasUuids, LogsActivity;

    public const SOURCE_TYPES = ['manual', 'epic', 'release'];

    protected $fillable = [
        'delivery_project_id',
        'source_type',
        'source_id',
        'title',
        'due_date',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function deliveryProject(): BelongsTo
    {
        return $this->belongsTo(DeliveryProject::class);
    }

    public function isAuto(): bool
    {
        return in_array($this->source_type, ['epic', 'release'], true);
    }
}
