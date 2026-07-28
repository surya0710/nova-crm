<?php

namespace App\Services\Navigation;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserUiPreference;

/**
 * Thin adapter kept for existing call sites. All workspace logic lives on NavigationService.
 */
class NavigationContextManager
{
    public function __construct(
        protected NavigationService $navigation,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forRequest(User $user, ?Organization $organization): array
    {
        return $this->navigation->forShell($user, $organization);
    }

    public function rememberWorkspace(User $user, Organization $organization, string $workspace): UserUiPreference
    {
        return $this->navigation->rememberWorkspace($user, $organization, $workspace);
    }
}
