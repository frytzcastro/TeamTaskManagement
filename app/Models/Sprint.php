<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Sprint extends Model
{
    use HasUuids, LogsActivity;

    public const STATUSES = ['planned', 'active', 'closed'];

    protected $fillable = [
        'delivery_project_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'goal',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
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

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
