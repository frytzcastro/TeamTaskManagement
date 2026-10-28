<?php

namespace App\Models\Dmaic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DmaicControlPlan extends Model
{
    use HasUuids, LogsActivity;

    protected $fillable = [
        'dmaic_profile_id',
        'title',
        'description',
        'owner_id',
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(DmaicControlMetric::class);
    }
}
