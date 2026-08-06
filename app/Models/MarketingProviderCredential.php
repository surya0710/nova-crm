<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MarketingProviderCredentialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Encrypted tenant-owned OAuth/API credentials (provider-agnostic).
 *
 * Platform app ID/secret stay in .env. Tenant tokens and account configuration
 * live here only. No Meta/Google-specific columns — use configuration JSON.
 */
class MarketingProviderCredential extends Model
{
    /** @use HasFactory<MarketingProviderCredentialFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'marketing_provider_id',
        'access_token',
        'refresh_token',
        'token_type',
        'scopes',
        'configuration',
        'expires_at',
        'metadata',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'scopes' => 'array',
            'configuration' => 'array',
            'metadata' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(MarketingProvider::class, 'marketing_provider_id');
    }

    public function isExpired(?\DateTimeInterface $at = null): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        $at = $at ?? now();

        return $this->expires_at->lessThanOrEqualTo($at);
    }
}
