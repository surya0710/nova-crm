<?php

namespace App\Models;

use Database\Factories\MarketingVisitorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Anonymous browser identity.
 *
 * Deliberately does NOT use BelongsToOrganization: visitors exist before any
 * tenant association, and the organization global scope would hide unowned
 * rows and force-assign the current tenant on create. Ownership is resolved
 * later, at attribution time (Phase 7B.4).
 */
class MarketingVisitor extends Model
{
    /** @use HasFactory<MarketingVisitorFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'visitor_uuid',
        'first_seen_at',
        'last_seen_at',
        'first_ip',
        'last_ip',
        'first_user_agent',
        'last_user_agent',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(MarketingSession::class, 'visitor_id');
    }

    public function attributions(): HasMany
    {
        return $this->hasMany(MarketingAttribution::class, 'marketing_visitor_id');
    }
}
