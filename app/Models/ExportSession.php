<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ExportSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ExportSession extends Model
{
    /** @use HasFactory<ExportSessionFactory> */
    use BelongsToOrganization, HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'organization_id',
        'initiated_by',
        'module',
        'entity_type',
        'format',
        'selection_mode',
        'status',
        'total_count',
        'processed_count',
        'record_ids',
        'filters',
        'columns',
        'metadata',
        'disk',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'download_token',
        'download_expires_at',
        'downloaded_at',
        'revoked_at',
        'last_error',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'record_ids' => 'array',
            'filters' => 'array',
            'columns' => 'array',
            'metadata' => 'array',
            'file_size' => 'integer',
            'download_expires_at' => 'datetime',
            'downloaded_at' => 'datetime',
            'revoked_at' => 'datetime',
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
            return $this->status === self::STATUS_COMPLETED ? 100 : 0;
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
            self::STATUS_REVOKED,
        ], true);
    }

    public function isDownloadable(): bool
    {
        if ($this->status !== self::STATUS_COMPLETED) {
            return false;
        }

        if ($this->revoked_at !== null) {
            return false;
        }

        if (! $this->file_path || ! $this->download_token) {
            return false;
        }

        if ($this->download_expires_at && $this->download_expires_at->isPast()) {
            return false;
        }

        $disk = $this->disk ?: config('export.disk', 'local');

        return Storage::disk($disk)->exists($this->file_path);
    }

    public function formattedFileSize(): string
    {
        $bytes = (int) ($this->file_size ?? 0);
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 2).' MB';
    }
}
