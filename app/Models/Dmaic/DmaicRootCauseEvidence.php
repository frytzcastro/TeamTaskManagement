<?php

namespace App\Models\Dmaic;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DmaicRootCauseEvidence extends Model
{
    use HasUuids;

    protected $table = 'dmaic_root_cause_evidence';

    protected $fillable = [
        'dmaic_root_cause_id',
        'title',
        'body',
        'evidence_url',
        'recorded_by',
    ];

    public function rootCause(): BelongsTo
    {
        return $this->belongsTo(DmaicRootCause::class, 'dmaic_root_cause_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
