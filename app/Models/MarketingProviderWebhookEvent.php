<?php

namespace App\Models;

use Database\Factories\MarketingProviderWebhookEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * Raw inbound provider webhook event (P7C.6).
 *
 * Foundation storage only — no CRM lead creation or Marketing Platform writes.
 * Written exclusively through MarketingProviderService.
 */
class MarketingProviderWebhookEvent extends Model
{
    /** @use HasFactory<MarketingProviderWebhookEventFactory> */
    use HasFactory;

    public const STATUS_RECEIVED = 'received';

    public const STATUS_DUPLICATE = 'duplicate';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_IGNORED = 'ignored';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_RECEIVED,
        self::STATUS_DUPLICATE,
        self::STATUS_REJECTED,
        self::STATUS_VERIFIED,
        self::STATUS_PROCESSING,
        self::STATUS_PROCESSED,
        self::STATUS_FAILED,
        self::STATUS_IGNORED,
    ];

    public const EVENT_VERIFICATION = 'verification';

    protected $fillable = [
        'organization_id',
        'provider',
        'event_type',
        'delivery_id',
        'payload',
        'signature',
        'received_at',
        'processed_at',
        'processing_status',
        'failure_reason',
        'processing_attempts',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
            'processing_attempts' => 'integer',
        ];
    }

    public static function assertValidStatus(string $status): void
    {
        if (! in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException("Invalid webhook processing status [{$status}].");
        }
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isReceived(): bool
    {
        return $this->processing_status === self::STATUS_RECEIVED;
    }

    public function isProcessed(): bool
    {
        return $this->processed_at !== null;
    }
}
