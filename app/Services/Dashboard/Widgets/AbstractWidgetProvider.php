<?php

namespace App\Services\Dashboard\Widgets;

use App\Contracts\DashboardWidgetDataProviderInterface;
use App\Models\Organization;
use App\Models\User;
use App\Services\Dashboard\ModuleSubscriptionService;

abstract class AbstractWidgetProvider implements DashboardWidgetDataProviderInterface
{
    public function __construct(
        protected ModuleSubscriptionService $subscriptionService,
    ) {}

    abstract public function key(): string;

    abstract public function subscriptionModule(): ?string;

    abstract public function permissionSlug(): ?string;

    /** @return array<string, mixed> */
    abstract protected function fetchData(User $user, Organization $organization, array $configuration): array;

    public function authorize(User $user, Organization $organization): bool
    {
        if (! $this->subscriptionService->moduleAllowed($organization, $this->subscriptionModule())) {
            return false;
        }

        $permission = $this->permissionSlug();
        if ($permission === null) {
            return true;
        }

        return $user->hasPermission($permission, $organization);
    }

    public function isVisible(User $user, Organization $organization): bool
    {
        return $this->authorize($user, $organization);
    }

    public function load(User $user, Organization $organization, array $configuration = []): array
    {
        return $this->fetchData($user, $organization, $configuration);
    }

    public function configurationSchema(): array
    {
        return [];
    }

    public function refreshInterval(): ?int
    {
        return (int) config('dashboard.cache_ttl', 300);
    }

    public function cacheKey(User $user, Organization $organization): string
    {
        return sprintf(
            'dashboard.widget.%d.%d.%s',
            $organization->id,
            $user->id,
            $this->key()
        );
    }
}
