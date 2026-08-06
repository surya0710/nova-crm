<?php

namespace App\Models;

use Database\Factories\PlatformUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class PlatformUser extends Authenticatable
{
    /** @use HasFactory<PlatformUserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'locked_at',
        'failed_login_attempts',
        'last_login_at',
        'two_factor_ready',
        'preferences',
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
            'locked_at' => 'datetime',
            'two_factor_ready' => 'boolean',
            'preferences' => 'array',
            'failed_login_attempts' => 'integer',
        ];
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function dashboardLayout(): array
    {
        $layout = $this->preferences['dashboard_layout']['platform'] ?? null;

        if (is_array($layout) && $layout !== []) {
            return $layout;
        }

        return config('platform.dashboard_widgets', []);
    }

    public function setDashboardLayout(array $widgets): void
    {
        $preferences = $this->preferences ?? [];
        $preferences['dashboard_layout']['platform'] = array_values($widgets);
        $this->preferences = $preferences;
        $this->save();
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
