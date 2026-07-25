<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\Rbac\AuthorizationService;
use App\Services\TenantContext;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_super_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot(['role', 'role_id', 'is_owner'])
            ->withTimestamps();
    }

    public function ownedOrganizations(): BelongsToMany
    {
        return $this->organizations()->wherePivot('is_owner', true);
    }

    public function belongsToOrganization(Organization|int $organization): bool
    {
        $organizationId = $organization instanceof Organization
            ? $organization->id
            : $organization;

        return $this->organizations()
            ->where('organizations.id', $organizationId)
            ->exists();
    }

    public function isOwnerOf(Organization|int $organization): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $organizationId = $organization instanceof Organization
            ? $organization->id
            : $organization;

        return $this->organizations()
            ->where('organizations.id', $organizationId)
            ->where(function ($query) {
                $query->where('organization_user.is_owner', true)
                    ->orWhere('organization_user.role', 'organization-owner');
            })
            ->exists();
    }

    public function hasOrganizations(): bool
    {
        return $this->organizations()->exists();
    }

    public function getRoleInOrganization(Organization|int|null $organization = null): ?Role
    {
        $organization = $this->resolveOrganization($organization);

        if (! $organization) {
            return null;
        }

        $pivot = $this->organizations()
            ->where('organizations.id', $organization->id)
            ->first()
            ?->pivot;

        if (! $pivot?->role_id) {
            return null;
        }

        return Role::query()->find($pivot->role_id);
    }

    public function getRoleNameInOrganization(Organization|int|null $organization = null): ?string
    {
        return $this->getRoleInOrganization($organization)?->name;
    }

    public function userRoles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function hasPermission(string $permission, Organization|int|null $organization = null): bool
    {
        return app(AuthorizationService::class)->can($this, $permission, $organization);
    }

    public function hasAnyPermission(array $permissions, Organization|int|null $organization = null): bool
    {
        return app(AuthorizationService::class)->canAny($this, $permissions, $organization);
    }

    public function effectivePermissions(Organization|int|null $organization = null): \Illuminate\Support\Collection
    {
        $organization = $this->resolveOrganization($organization);

        if (! $organization) {
            return collect();
        }

        return app(AuthorizationService::class)->effectivePermissions($this, $organization);
    }

    protected function resolveOrganization(Organization|int|null $organization): ?Organization
    {
        if ($organization instanceof Organization) {
            return $organization;
        }

        if (is_int($organization)) {
            return Organization::query()->find($organization);
        }

        return app(TenantContext::class)->get();
    }
}
