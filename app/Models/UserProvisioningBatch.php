<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProvisioningBatch extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'initiated_by',
        'status',
        'total',
        'processed',
        'succeeded',
        'skipped',
        'failed',
        'options',
        'errors',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'errors' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function markStarted(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markFinished(): void
    {
        $this->update([
            'status' => $this->failed > 0 && $this->succeeded === 0 ? 'failed' : 'completed',
            'finished_at' => now(),
        ]);
    }

    public function incrementCounters(string $outcome): void
    {
        $column = match ($outcome) {
            'succeeded' => 'succeeded',
            'skipped' => 'skipped',
            'failed' => 'failed',
            default => null,
        };

        if ($column === null) {
            return;
        }

        $this->increment('processed');
        $this->increment($column);
    }
}
