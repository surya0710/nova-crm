<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueJobRun extends Model
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'organization_id',
        'job_uuid',
        'job_id',
        'connection',
        'queue',
        'job_name',
        'attempt',
        'status',
        'worker_id',
        'started_at',
        'finished_at',
        'duration_ms',
        'exception_class',
        'exception_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'attempt' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'duration_ms' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
