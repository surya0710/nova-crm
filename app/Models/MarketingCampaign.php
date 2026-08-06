<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MarketingCampaign extends Model
{
    use BelongsToOrganization;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_PAUSED,
        self::STATUS_COMPLETED,
    ];

    protected $fillable = [
        'organization_id',
        'created_by',
        'name',
        'slug',
        'status',
        'description',
        'budget_amount',
        'budget_currency',
        'channels',
        'audience',
        'utm_campaign',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'budget_amount' => 'decimal:2',
            'channels' => 'array',
            'audience' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MarketingCampaign $campaign) {
            if (! $campaign->slug) {
                $campaign->slug = Str::slug($campaign->name).'-'.Str::lower(Str::random(4));
            }
            if (! $campaign->utm_campaign) {
                $campaign->utm_campaign = $campaign->slug;
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => __('Active'),
            self::STATUS_PAUSED => __('Paused'),
            self::STATUS_COMPLETED => __('Completed'),
            default => __('Draft'),
        };
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
