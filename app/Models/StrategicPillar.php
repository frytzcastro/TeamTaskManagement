<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StrategicPillar extends Model
{
    use HasUuids, LogsActivity;

    protected $table = 'strategic_pillars';

    protected $fillable = ['name', 'description', 'status'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function initiatives(): HasMany
    {
        return $this->hasMany(Initiative::class);
    }
}
