<?php

namespace App\Services\Lookup;

use App\Models\Organization;
use App\Models\User;
use App\Services\Dashboard\ModuleSubscriptionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class LookupPlatformService
{
    public function __construct(
        protected LookupRegistry $registry,
        protected ModuleSubscriptionService $modules,
    ) {}

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int|bool>}
     */
    public function search(
        string $entity,
        Organization $organization,
        User $actor,
        string $query = '',
        int $page = 1,
        int $perPage = 0,
    ): array {
        $this->assertAuthorized($entity, $organization, $actor);

        $minLength = (int) config('lookups.min_search_length', 0);
        $trimmed = trim($query);
        if ($minLength > 0 && $trimmed !== '' && mb_strlen($trimmed) < $minLength) {
            return (new LookupPaginatedResult([], $page, $perPage, 0, false))->toArray();
        }

        $perPage = $this->normalizePerPage($perPage);
        $page = max(1, $page);

        $cacheKey = $this->cacheKey($entity, $organization->id, $trimmed, $page, $perPage);
        $ttl = (int) config('lookups.cache_ttl_seconds', 60);

        if ($trimmed === '' && $page === 1 && $ttl > 0) {
            return Cache::remember($cacheKey, $ttl, fn () => $this->performSearch($entity, $organization, $actor, $trimmed, $page, $perPage));
        }

        return $this->performSearch($entity, $organization, $actor, $trimmed, $page, $perPage);
    }

    public function find(string $entity, Organization $organization, User $actor, int|string $id): ?array
    {
        $this->assertAuthorized($entity, $organization, $actor);

        $provider = $this->registry->resolve($entity);

        if (! method_exists($provider, 'findOne')) {
            return null;
        }

        $result = $provider->findOne($organization, $id);

        return $result?->toArray();
    }

    protected function performSearch(
        string $entity,
        Organization $organization,
        User $actor,
        string $query,
        int $page,
        int $perPage,
    ): array {
        return $this->registry
            ->resolve($entity)
            ->search($organization, $actor, $query, $page, $perPage)
            ->toArray();
    }

    protected function assertAuthorized(string $entity, Organization $organization, User $actor): void
    {
        $meta = config('lookups.entities.'.$entity);

        if (! is_array($meta)) {
            throw new InvalidArgumentException("Unknown lookup entity [{$entity}].");
        }

        if (! $actor->belongsToOrganization($organization)) {
            throw new AuthorizationException('You are not a member of this organization.');
        }

        $license = $meta['license_module'] ?? null;
        if ($license && ! $this->modules->moduleAllowed($organization, $license)) {
            throw new AuthorizationException('This module is not licensed for your organization.');
        }

        $permission = $meta['permission'] ?? null;
        if ($permission && ! $actor->is_super_admin && ! $actor->isOwnerOf($organization)) {
            if (! $actor->hasPermission($permission, $organization)) {
                throw new AuthorizationException('You are not authorized to search this entity type.');
            }
        }
    }

    protected function normalizePerPage(int $perPage): int
    {
        $default = (int) config('lookups.per_page', 20);
        $max = (int) config('lookups.max_per_page', 50);
        $perPage = $perPage > 0 ? $perPage : $default;

        return min($perPage, $max);
    }

    protected function cacheKey(string $entity, int $organizationId, string $query, int $page, int $perPage): string
    {
        return sprintf('lookup:%s:%d:%s:%d:%d', $entity, $organizationId, md5($query), $page, $perPage);
    }
}
