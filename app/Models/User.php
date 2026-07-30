<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserAccountStatus;
use App\Services\Rbac\AuthorizationService;
use App\Services\TenantContext;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'account_status',
        'portal_access_enabled',
        'locked_at',
        'disabled_at',
        'failed_login_attempts',
        'last_login_at',
        'last_logout_at',
        'login_count',
        'password_changed_at',
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
            'account_status' => UserAccountStatus::class,
            'portal_access_enabled' => 'boolean',
            'locked_at' => 'datetime',
            'disabled_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_logout_at' => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot(['id', 'role', 'role_id', 'is_owner', 'is_active'])
            ->withTimestamps();
    }

    public function activeOrganizations(): BelongsToMany
    {
        return $this->organizations()
            ->wherePivot('is_active', true)
            ->where('organizations.is_active', true)
            ->where('organizations.status', 'active')
            ->whereNull('organizations.archived_at');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(UserInvitation::class);
    }

    public function latestInvitation(): HasOne
    {
        return $this->hasOne(UserInvitation::class)->latestOfMany();
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function displayAccountStatus(): string
    {
        $status = $this->account_status ?? UserAccountStatus::Active;

        if ($status === UserAccountStatus::PendingInvitation) {
            $invitation = $this->latestInvitation;
            if ($invitation && $invitation->isExpired()) {
                return 'invitation_expired';
            }
        }

        return $status->value;
    }

    public function displayAccountStatusLabel(): string
    {
        return match ($this->displayAccountStatus()) {
            'invitation_expired' => __('Invitation Expired'),
            default => ($this->account_status ?? UserAccountStatus::Active)->label(),
        };
    }

    public function canAuthenticate(): bool
    {
        $status = $this->account_status ?? UserAccountStatus::Active;

        return $status->canAuthenticate();
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

    public function belongsToActiveOrganization(Organization|int $organization): bool
    {
        $organizationId = $organization instanceof Organization
            ? $organization->id
            : $organization;

        return $this->activeOrganizations()
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
