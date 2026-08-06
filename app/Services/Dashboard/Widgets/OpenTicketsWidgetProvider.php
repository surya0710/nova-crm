<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use App\Services\TenantContext;

class OpenTicketsWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'open_tickets';
    }

    public function subscriptionModule(): ?string
    {
        return 'support';
    }

    public function permissionSlug(): ?string
    {
        return 'tasks.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $open = Task::query()
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        return ['open_count' => $open];
    }
}
