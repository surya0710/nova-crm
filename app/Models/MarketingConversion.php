<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MarketingConversionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Immutable marketing conversion event (P7B.5).
 *
 * Append-only business events resolved through MarketingAttribution.
 * Marketing tracking metadata is never stored here — it lives on touches.
 */
class MarketingConversion extends Model
{
    /** @use HasFactory<MarketingConversionFactory> */
    use BelongsToOrganization, HasFactory;

    public const LEAD_CREATED = 'lead_created';

    public const LEAD_CONVERTED = 'lead_converted';

    public const CUSTOMER_CREATED = 'customer_created';

    public const OPPORTUNITY_CREATED = 'opportunity_created';

    public const OPPORTUNITY_WON = 'opportunity_won';

    /** @var list<string> */
    public const SUPPORTED_EVENTS = [
        self::LEAD_CREATED,
        self::LEAD_CONVERTED,
        self::CUSTOMER_CREATED,
        self::OPPORTUNITY_CREATED,
        self::OPPORTUNITY_WON,
    ];

    protected $fillable = [
        'organization_id',
        'marketing_attribution_id',
        'lead_id',
        'customer_id',
        'opportunity_id',
        'event_name',
        'event_value',
        'currency',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'event_value' => 'decimal:2',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('Marketing conversion events are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new RuntimeException('Marketing conversion events are immutable and cannot be deleted.');
        });
    }

    public function attribution(): BelongsTo
    {
        return $this->belongsTo(MarketingAttribution::class, 'marketing_attribution_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }
}
