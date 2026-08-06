<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkOperation extends Model
{
    use BelongsToOrganization;

    public const STATUS_PENDING = 'pending';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'organization_id',
        'initiated_by',
        'module',
        'entity_type',
        'action_key',
        'selection_mode',
        'status',
        'total_count',
        'processed_count',
        'success_count',
        'failed_count',
        'skipped_count',
        'record_ids',
        'filters',
        'input',
        'failures',
        'last_error',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'record_ids' => 'array',
            'filters' => 'array',
            'input' => 'array',
            'failures' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function progressPercent(): int
    {
        if ($this->total_count <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->processed_count / $this->total_count) * 100));
    }

    public function durationSeconds(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        $end = $this->completed_at ?? now();

        return max(0, $this->started_at->diffInSeconds($end));
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
        ], true);
    }
}
