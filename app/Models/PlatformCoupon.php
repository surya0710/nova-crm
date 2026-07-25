<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformCoupon extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'applies_to_plan',
        'max_redemptions',
        'redemptions',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function isRedeemable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        if ($this->max_redemptions !== null && $this->redemptions >= $this->max_redemptions) {
            return false;
        }

        return true;
    }
}
