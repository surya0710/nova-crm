<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MarketingProviderUploadedConversionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dedup registry for provider offline conversion uploads (P7C.9).
 * Written exclusively through MarketingProviderService.
 */
class MarketingProviderUploadedConversion extends Model
{
    /** @use HasFactory<MarketingProviderUploadedConversionFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'marketing_provider_id',
        'marketing_conversion_id',
        'external_event_id',
        'provider_event_name',
        'metadata',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'uploaded_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(MarketingProvider::class, 'marketing_provider_id');
    }

    public function conversion(): BelongsTo
    {
        return $this->belongsTo(MarketingConversion::class, 'marketing_conversion_id');
    }
}
