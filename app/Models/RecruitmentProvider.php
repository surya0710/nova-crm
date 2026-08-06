<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use InvalidArgumentException;

/**
 * Tenant-owned recruitment provider connection (Phase 11.6).
 */
class RecruitmentProvider extends Model
{
    use BelongsToOrganization;

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
        'category',
        'status',
        'external_account_id',
        'capabilities',
        'configuration',
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
            'configuration' => 'array',
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
            throw new InvalidArgumentException("Invalid recruitment provider status [{$status}].");
        }
    }

    public function isConnected(): bool
    {
        return $this->status === self::STATUS_CONNECTED;
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
        return $this->hasOne(RecruitmentProviderCredential::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(RecruitmentCalendarEvent::class);
    }

    public function jobBoardListings(): HasMany
    {
        return $this->hasMany(RecruitmentJobBoardListing::class);
    }
}
