<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Epic extends Model
{
    use HasUuids, LogsActivity;

    protected $fillable = [
        'system_id',
        'title',
        'description',
        'priority',
        'status',
        'start_date',
        'due_date',
        'rolled_start_date',
        'rolled_due_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'rolled_start_date' => 'date',
            'rolled_due_date' => 'date',
        ];
    }

    public function scheduleBeyondPlanned(): bool
    {
        if (! $this->due_date || ! $this->rolled_due_date) {
            return false;
        }

        return $this->rolled_due_date->gt($this->due_date);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(Feature::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
