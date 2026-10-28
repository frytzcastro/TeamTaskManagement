<?php

namespace App\Models\Dmaic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DmaicRootCause extends Model
{
    use HasUuids, LogsActivity;

    public const STATUSES = ['suspected', 'validated', 'rejected'];

    protected $fillable = [
        'dmaic_profile_id',
        'title',
        'method',
        'description',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(DmaicProfile::class, 'dmaic_profile_id');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(DmaicRootCauseEvidence::class);
    }

    public function improvements(): HasMany
    {
        return $this->hasMany(DmaicImprovement::class);
    }
}
