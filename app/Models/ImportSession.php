<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ImportSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * Tenant-owned import session for the Import Platform.
 *
 * Lifecycle writes are owned exclusively by ImportPlatformService.
 */
class ImportSession extends Model
{
    /** @use HasFactory<ImportSessionFactory> */
    use BelongsToOrganization, HasFactory;

    public const STATUS_UPLOADED = 'uploaded';

    public const STATUS_VALIDATING = 'validating';

    public const STATUS_READY = 'ready';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_IMPORTING = 'importing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_UPLOADED,
        self::STATUS_VALIDATING,
        self::STATUS_READY,
        self::STATUS_QUEUED,
        self::STATUS_IMPORTING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
    ];

    /** @var list<string> */
    public const TERMINAL_STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
    ];

    /**
     * Allowed status transitions for the foundation lifecycle.
     *
     * @var array<string, list<string>>
     */
    public const ALLOWED_TRANSITIONS = [
        self::STATUS_UPLOADED => [
            self::STATUS_VALIDATING,
            self::STATUS_CANCELLED,
            self::STATUS_FAILED,
        ],
        self::STATUS_VALIDATING => [
            self::STATUS_READY,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
        ],
        self::STATUS_READY => [
            self::STATUS_QUEUED,
            self::STATUS_IMPORTING,
            self::STATUS_CANCELLED,
            self::STATUS_FAILED,
        ],
        self::STATUS_QUEUED => [
            self::STATUS_IMPORTING,
            self::STATUS_CANCELLED,
            self::STATUS_FAILED,
        ],
        self::STATUS_IMPORTING => [
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
        ],
        self::STATUS_COMPLETED => [],
        self::STATUS_FAILED => [],
        self::STATUS_CANCELLED => [],
    ];

    protected $fillable = [
        'organization_id',
        'entity_type',
        'original_filename',
        'stored_path',
        'disk',
        'mime_type',
        'file_size',
        'uploaded_by',
        'status',
        'worksheet_name',
        'column_mapping',
        'detected_headers',
        'validation_summary',
        'started_at',
        'completed_at',
        'total_rows',
        'processed_rows',
        'created_count',
        'updated_count',
        'skipped_count',
        'failed_count',
        'last_error',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'column_mapping' => 'array',
            'detected_headers' => 'array',
            'validation_summary' => 'array',
            'metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'file_size' => 'integer',
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'created_count' => 'integer',
            'updated_count' => 'integer',
            'skipped_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    public static function assertValidStatus(string $status): void
    {
        if (! in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException("Invalid import session status [{$status}].");
        }
    }

    public static function assertValidTransition(string $from, string $to): void
    {
        self::assertValidStatus($from);
        self::assertValidStatus($to);

        $allowed = self::ALLOWED_TRANSITIONS[$from] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw new InvalidArgumentException(
                "Invalid import session status transition [{$from} -> {$to}]."
            );
        }
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
