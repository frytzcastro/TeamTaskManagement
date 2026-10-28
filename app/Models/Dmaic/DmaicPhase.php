<?php

namespace App\Models\Dmaic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DmaicPhase extends Model
{
    use HasUuids, LogsActivity;

    protected $fillable = [
        'dmaic_profile_id',
        'phase',
        'objectives',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
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

    public function gate(): HasOne
    {
        return $this->hasOne(DmaicGate::class)->latestOfMany();
    }

    public function gates(): HasMany
    {
        return $this->hasMany(DmaicGate::class);
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(DmaicPhaseDeliverable::class);
    }
}
