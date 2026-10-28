<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use App\Models\Dmaic\DmaicProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BusinessProblem extends Model
{
    use HasUuids, LogsActivity;

    protected $fillable = [
        'initiative_id',
        'title',
        'problem_statement',
        'business_impact',
        'customer_impact',
        'opportunity',
        'status',
        'owner_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function initiative(): BelongsTo
    {
        return $this->belongsTo(Initiative::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function dmaicProfiles(): HasMany
    {
        return $this->hasMany(DmaicProfile::class);
    }
}
