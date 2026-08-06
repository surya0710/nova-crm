<?php

namespace App\Contracts;

use App\Models\Organization;
use App\Models\User;

interface DashboardWidgetDataProviderInterface
{
    public function key(): string;

    public function authorize(User $user, Organization $organization): bool;

    public function isVisible(User $user, Organization $organization): bool;

    public function subscriptionModule(): ?string;

    public function permissionSlug(): ?string;

    /** @return array<string, mixed> */
    public function load(User $user, Organization $organization, array $configuration = []): array;

    /** @return array<string, mixed> */
    public function configurationSchema(): array;

    public function refreshInterval(): ?int;

    public function cacheKey(User $user, Organization $organization): string;
}
