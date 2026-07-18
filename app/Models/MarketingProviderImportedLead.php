<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MarketingProviderImportedLeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dedup registry linking provider lead entries to CRM leads (P7C.5).
 * Written exclusively through MarketingProviderService.
 */
class MarketingProviderImportedLead extends Model
{
    /** @use HasFactory<MarketingProviderImportedLeadFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'marketing_provider_id',
        'lead_id',
        'external_lead_id',
        'external_form_id',
        'raw_payload',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'imported_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(MarketingProvider::class, 'marketing_provider_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
