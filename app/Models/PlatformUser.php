<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class PlatformUser extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\PlatformUserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'last_login_at',
        'two_factor_ready',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'two_factor_ready' => 'boolean',
        ];
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(PlatformAuditLog::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function roleName(): string
    {
        return config("platform.roles.{$this->role}.name", $this->role);
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        $roleConfig = config("platform.roles.{$this->role}");

        if (! $roleConfig) {
            return false;
        }

        $permissions = $roleConfig['permissions'] ?? [];

        if ($permissions === '*') {
            return true;
        }

        return in_array($permission, $permissions, true);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function isPlatformOwner(): bool
    {
        return $this->role === 'platform-owner';
    }
}
