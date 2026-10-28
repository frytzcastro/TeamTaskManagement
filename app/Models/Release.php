<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Release extends Model
{
    use HasUuids, LogsActivity;

    public const STATUSES = ['planned', 'in_qa', 'approved', 'deployed', 'rolled_back'];

    public const ENVIRONMENTS = ['staging', 'production'];

    protected $fillable = [
        'delivery_project_id',
        'system_id',
        'name',
        'version',
        'environment',
        'status',
        'target_date',
        'released_at',
        'approved_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'released_at' => 'datetime',
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

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReleaseItem::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }
}
