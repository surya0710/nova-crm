<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\User;

class WelcomeWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'welcome';
    }

    public function subscriptionModule(): ?string
    {
        return 'common';
    }

    public function permissionSlug(): ?string
    {
        return null;
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        return [
            'user_name' => $user->name,
            'organization_name' => $organization->name,
            'greeting' => $this->greeting(),
        ];
    }

    protected function greeting(): string
    {
        $hour = (int) now()->format('G');

        if ($hour < 12) {
            return 'Good morning';
        }

        if ($hour < 17) {
            return 'Good afternoon';
        }

        return 'Good evening';
    }
}
