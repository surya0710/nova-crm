<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueWorkerHeartbeat extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_STOPPED = 'stopped';

    protected $fillable = [
        'worker_id',
        'hostname',
        'process_id',
        'connection',
        'queue',
        'status',
        'started_at',
        'last_seen_at',
        'stopped_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'process_id' => 'integer',
            'started_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'stopped_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
