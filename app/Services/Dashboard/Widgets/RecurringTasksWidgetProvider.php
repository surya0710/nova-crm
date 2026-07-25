<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\TaskRecurrence;
use App\Models\User;
use App\Services\TenantContext;

class RecurringTasksWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'recurring_tasks';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'projects.recurrence.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $query = TaskRecurrence::query()->where('is_active', true);

        $recurrences = (clone $query)
            ->with(['task:id,title,status,due_at,due_date,assigned_to'])
            ->orderBy('next_run_at')
            ->limit(5)
            ->get();

        return [
            'count' => (clone $query)->count(),
            'recurrences' => $recurrences,
        ];
    }
}
