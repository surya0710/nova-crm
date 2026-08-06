<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MarketingProviderLeadFormFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Local catalog of provider lead-form metadata (P7C.4).
 *
 * Stores form definitions only — never lead submissions.
 * Written exclusively through MarketingProviderService.
 */
class MarketingProviderLeadForm extends Model
{
    /** @use HasFactory<MarketingProviderLeadFormFactory> */
    use BelongsToOrganization, HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'organization_id',
        'marketing_provider_id',
        'external_form_id',
        'external_page_id',
        'name',
        'status',
        'locale',
        'questions',
        'raw_metadata',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'questions' => 'array',
            'raw_metadata' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(MarketingProvider::class, 'marketing_provider_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function questionCount(): int
    {
        return is_array($this->questions) ? count($this->questions) : 0;
    }

    /**
     * Provider-reported form status (ACTIVE, ARCHIVED, …) when available.
     */
    public function providerStatus(): ?string
    {
        $meta = $this->raw_metadata ?? [];

        $status = $meta['provider_status'] ?? null;

        return is_string($status) && $status !== '' ? $status : null;
    }
}
