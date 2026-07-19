<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\WorkflowExecutionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowExecution extends Model
{
    /** @use HasFactory<WorkflowExecutionFactory> */
    use BelongsToOrganization, HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUSES = [
        self::STATUS_PENDING, self::STATUS_RUNNING, self::STATUS_COMPLETED,
        self::STATUS_FAILED, self::STATUS_CANCELLED, self::STATUS_SKIPPED,
    ];

    protected $fillable = [
        'organization_id', 'workflow_id', 'workflow_version', 'trigger_subject_type',
        'trigger_subject_id', 'trigger_subject_snapshot', 'trigger_payload', 'status',
        'idempotency_key', 'lock_owner', 'lock_acquired_at', 'heartbeat_at', 'attempt',
        'current_action_position', 'queued_at', 'started_at', 'finished_at',
        'error_message', 'result',
    ];

    protected function casts(): array
    {
        return [
            'trigger_subject_snapshot' => 'array',
            'trigger_payload' => 'array',
            'result' => 'array',
            'workflow_version' => 'integer',
            'attempt' => 'integer',
            'current_action_position' => 'integer',
            'lock_acquired_at' => 'datetime',
            'heartbeat_at' => 'datetime',
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function triggerSubject(): MorphTo
    {
        return $this->morphTo();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WorkflowExecutionLog::class)
            ->orderBy('occurred_at')
            ->orderBy('id');
    }
}
