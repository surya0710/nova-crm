<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ClientUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class ClientUser extends Authenticatable
{
    /** @use HasFactory<ClientUserFactory> */
    use Auditable, BelongsToOrganization, HasFactory, Notifiable;

    protected $fillable = [
        'organization_id',
        'customer_id',
        'name',
        'email',
        'password',
        'email_verified_at',
        'last_login_at',
        'invited_at',
        'invited_by',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'invited_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function projectAccess(): HasMany
    {
        return $this->hasMany(ClientProjectAccess::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(ClientNotification::class)->latest();
    }
}
