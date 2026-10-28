<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Deployment extends Model
{
    use HasUuids, LogsActivity;

    public const STATUSES = ['pending', 'success', 'failed'];

    protected $fillable = [
        'release_id',
        'environment',
        'status',
        'deployed_at',
        'deployed_by',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'deployed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }

    public function deployer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deployed_by');
    }
}
