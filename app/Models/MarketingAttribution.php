<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MarketingAttributionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relationship between anonymous marketing identity and CRM entities.
 *
 * Attribution is a relationship, not a mutation: marketing metadata stays
 * on MarketingTouch / visitor history. CRM entities only hold FKs here.
 */
class MarketingAttribution extends Model
{
    /** @use HasFactory<MarketingAttributionFactory> */
    use BelongsToOrganization, HasFactory;

    public const MODEL_FIRST_TOUCH = 'first_touch';

    protected $fillable = [
        'organization_id',
        'marketing_visitor_id',
        'marketing_session_id',
        'lead_id',
        'customer_id',
        'opportunity_id',
        'attribution_model',
        'is_primary',
        'attributed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'attributed_at' => 'datetime',
        ];
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(MarketingVisitor::class, 'marketing_visitor_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(MarketingSession::class, 'marketing_session_id');
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
