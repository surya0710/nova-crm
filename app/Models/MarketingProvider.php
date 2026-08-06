<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MarketingProviderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use InvalidArgumentException;

/**
 * Tenant-owned marketing provider connection (P7C.1).
 *
 * Provider-agnostic: slug identifies the adapter; no Meta/Google-specific columns.
 */
class MarketingProvider extends Model
{
    /** @use HasFactory<MarketingProviderFactory> */
    use BelongsToOrganization, HasFactory;

    public const STATUS_CONNECTED = 'connected';

    public const STATUS_DISCONNECTED = 'disconnected';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_ERROR = 'error';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_CONNECTED,
        self::STATUS_DISCONNECTED,
        self::STATUS_EXPIRED,
        self::STATUS_ERROR,
    ];

    protected $fillable = [
        'organization_id',
        'slug',
        'display_name',
        'status',
        'external_account_id',
        'capabilities',
        'metadata',
        'last_error',
        'last_synced_at',
        'last_health_at',
        'connected_at',
        'disconnected_at',
    ];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'metadata' => 'array',
            'last_synced_at' => 'datetime',
            'last_health_at' => 'datetime',
            'connected_at' => 'datetime',
            'disconnected_at' => 'datetime',
        ];
    }

    public static function assertValidStatus(string $status): void
    {
        if (! in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException("Invalid marketing provider status [{$status}].");
        }
    }

    public function isConnected(): bool
    {
        return $this->status === self::STATUS_CONNECTED;
    }

    public function isDisconnected(): bool
    {
        return $this->status === self::STATUS_DISCONNECTED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_CONNECTED => 'Connected',
            self::STATUS_DISCONNECTED => 'Disconnected',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_ERROR => 'Error',
            default => ucfirst((string) $this->status),
        };
    }

    public function credential(): HasOne
    {
        return $this->hasOne(MarketingProviderCredential::class);
    }

    public function leadForms(): HasMany
    {
        return $this->hasMany(MarketingProviderLeadForm::class);
    }

    public function syncRuns(): HasMany
    {
        return $this->hasMany(MarketingProviderSyncRun::class);
    }

    public function uploadedConversions(): HasMany
    {
        return $this->hasMany(MarketingProviderUploadedConversion::class);
    }
}
