<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ReleaseItem extends Model
{
    use HasUuids;

    public const TYPES = ['task', 'feature', 'bug'];

    protected $fillable = [
        'release_id',
        'item_type',
        'item_id',
    ];

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }

    public function item(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'item_type', 'item_id');
    }

    public function resolveRelated(): ?Model
    {
        return match ($this->item_type) {
            'task' => Task::find($this->item_id),
            'feature' => Feature::find($this->item_id),
            'bug' => Bug::find($this->item_id),
            default => null,
        };
    }
}
