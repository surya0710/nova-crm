<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MarketingProviderSyncRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * Immutable-history execution record for provider synchronization.
 *
 * Lifecycle updates are written exclusively through MarketingProviderService.
 */
class MarketingProviderSyncRun extends Model
{
    /** @use HasFactory<MarketingProviderSyncRunFactory> */
    use BelongsToOrganization, HasFactory;

    public const TYPE_LEAD_IMPORT = 'lead_import';

    public const TYPE_WEBHOOK_PROCESSING = 'webhook_processing';

    public const TYPE_ASSET_DISCOVERY = 'asset_discovery';

    public const TYPE_FORM_SYNC = 'form_sync';

    public const TYPE_CONVERSION_UPLOAD = 'conversion_upload';

    /** @var list<string> */
    public const SYNC_TYPES = [
        self::TYPE_LEAD_IMPORT,
        self::TYPE_WEBHOOK_PROCESSING,
        self::TYPE_ASSET_DISCOVERY,
        self::TYPE_FORM_SYNC,
        self::TYPE_CONVERSION_UPLOAD,
    ];

    public const DIRECTION_INBOUND = 'inbound';

    public const DIRECTION_OUTBOUND = 'outbound';

    /** @var list<string> */
    public const DIRECTIONS = [
        self::DIRECTION_INBOUND,
        self::DIRECTION_OUTBOUND,
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_RUNNING,
        self::STATUS_COMPLETED,
        self::STATUS_PARTIAL,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
    ];

    /** @var list<string> */
    public const TERMINAL_STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_PARTIAL,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'organization_id',
        'marketing_provider_id',
        'sync_type',
        'direction',
        'status',
        'started_at',
        'finished_at',
        'records_processed',
        'records_succeeded',
        'records_failed',
        'message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'records_processed' => 'integer',
            'records_succeeded' => 'integer',
            'records_failed' => 'integer',
            'metadata' => 'array',
        ];
    }

    public static function assertValidSyncType(string $syncType): void
    {
        if (! in_array($syncType, self::SYNC_TYPES, true)) {
            throw new InvalidArgumentException("Invalid marketing provider sync type [{$syncType}].");
        }
    }

    public static function assertValidDirection(string $direction): void
    {
        if (! in_array($direction, self::DIRECTIONS, true)) {
            throw new InvalidArgumentException("Invalid marketing provider sync direction [{$direction}].");
        }
    }

    public static function assertValidStatus(string $status): void
    {
        if (! in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException("Invalid marketing provider sync status [{$status}].");
        }
    }

    public function isFinished(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    public function durationInSeconds(): ?int
    {
        if ($this->started_at === null || $this->finished_at === null) {
            return null;
        }

        return (int) $this->started_at->diffInSeconds($this->finished_at);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(MarketingProvider::class, 'marketing_provider_id');
    }
}
