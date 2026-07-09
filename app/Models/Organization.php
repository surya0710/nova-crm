<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Organization extends Model
{
    /** @use HasFactory<\Database\Factories\OrganizationFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'email',
        'phone',
        'website',
        'description',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'tax_name',
        'tax_number',
        'timezone',
        'currency',
        'settings',
        'is_active',
        'status',
        'plan',
        'last_activity_at',
        'archived_at',
        'storage_used_bytes',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
            'status' => OrganizationStatus::class,
            'last_activity_at' => 'datetime',
            'archived_at' => 'datetime',
            'storage_used_bytes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Organization $organization) {
            if (empty($organization->slug)) {
                $organization->slug = static::generateUniqueSlug($organization->name);
            }
        });

        static::created(function (Organization $organization) {
            app(\App\Services\OrganizationRoleService::class)->seedDefaultRoles($organization);
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $original.'-'.$count;
            $count++;
        }

        return $slug;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'role_id', 'is_owner'])
            ->withTimestamps();
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function templateApplications(): HasMany
    {
        return $this->hasMany(OrganizationTemplateApplication::class);
    }

    public function initialTemplateApplication(): ?OrganizationTemplateApplication
    {
        return $this->templateApplications()
            ->where('application_type', 'initial_onboarding')
            ->with(['template', 'version'])
            ->first();
    }

    public function owners(): BelongsToMany
    {
        return $this->users()->wherePivot('is_owner', true);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo || ! Storage::disk('public')->exists($this->logo)) {
            return null;
        }

        $path = 'storage/'.$this->logo;

        if (app()->runningInConsole() || ! request()->hasHeader('Host')) {
            return asset($path);
        }

        $base = rtrim(request()->getSchemeAndHttpHost().request()->getBaseUrl(), '/');

        return $base.'/'.$path;
    }

    public function hasLogo(): bool
    {
        return $this->logo_url !== null;
    }

    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim($this->name)) ?: [];

        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1).substr($words[1], 0, 1));
        }

        return strtoupper(substr($this->name, 0, 2));
    }

    public function addMember(User $user, string $roleSlug = 'employee'): void
    {
        $role = $this->roles()->where('slug', $roleSlug)->firstOrFail();

        $this->users()->attach($user->id, [
            'role_id' => $role->id,
            'role' => $roleSlug,
            'is_owner' => $roleSlug === 'organization-owner',
        ]);
    }

    public function primaryOwner(): ?User
    {
        return $this->owners()->first() ?? $this->users()->first();
    }

    public function isActive(): bool
    {
        return $this->status === OrganizationStatus::Active;
    }

    public function isSuspended(): bool
    {
        return $this->status === OrganizationStatus::Suspended;
    }

    public function isArchived(): bool
    {
        return $this->status === OrganizationStatus::Archived;
    }

    public function planLabel(): string
    {
        return config("platform.plans.{$this->plan}", $this->plan);
    }

    public function touchActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    public function syncActiveFlag(): void
    {
        $this->update(['is_active' => $this->isActive()]);
    }
}
