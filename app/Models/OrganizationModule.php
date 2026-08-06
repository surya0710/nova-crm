<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationModule extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'module_key',
        'is_enabled',
        'source',
        'included_in_subscription',
        'is_trial',
        'is_addon',
        'expires_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'included_in_subscription' => 'boolean',
            'is_trial' => 'boolean',
            'is_addon' => 'boolean',
            'expires_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isEffectivelyEnabled(): bool
    {
        return $this->is_enabled && ! $this->isExpired();
    }
}
