<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'organization_id',
        'slug',
        'name',
        'description',
        'color',
        'hierarchy_level',
        'is_system',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'hierarchy_level' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withTimestamps()
            ->withPivot('organization_id');
    }

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function hasPermission(string $permission): bool
    {
        if (in_array($this->slug, ['organization-owner', 'organization-administrator', 'platform-administrator'], true)) {
            return true;
        }

        if (! $this->relationLoaded('permissions')) {
            $this->load('permissions');
        }

        return $this->permissions->contains('slug', $permission);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
