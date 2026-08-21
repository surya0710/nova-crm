<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class CrmEmailWebhookEndpoint extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'provider',
        'token',
        'signing_secret',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CrmEmailWebhookEvent::class, 'endpoint_id');
    }

    public function setSigningSecretAttribute(?string $value): void
    {
        $this->attributes['signing_secret'] = filled($value) ? Crypt::encryptString($value) : null;
    }

    public function decryptedSigningSecret(): ?string
    {
        if (! filled($this->attributes['signing_secret'] ?? null)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->attributes['signing_secret']);
        } catch (\Throwable) {
            return null;
        }
    }

    public function url(): string
    {
        return route('webhooks.email', [
            'provider' => $this->provider,
            'token' => $this->token,
        ]);
    }
}
