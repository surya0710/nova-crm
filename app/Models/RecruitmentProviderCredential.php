<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentProviderCredential extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'recruitment_provider_id',
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
        return $this->belongsTo(RecruitmentProvider::class, 'recruitment_provider_id');
    }

    public function isExpired(?\DateTimeInterface $at = null): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->lessThanOrEqualTo($at ?? now());
    }
}
