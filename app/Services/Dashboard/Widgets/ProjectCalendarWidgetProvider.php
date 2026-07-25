<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\ProjectCalendarLink;
use App\Models\User;
use App\Services\TenantContext;

class ProjectCalendarWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'project_calendar';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'projects.calendar.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $query = ProjectCalendarLink::query()
            ->where(function ($builder) {
                $builder->whereDate('starts_at', '>=', today())
                    ->orWhereDate('due_date', '>=', today());
            });

        $events = (clone $query)
            ->with(['project:id,name,slug,project_number', 'task:id,title'])
            ->orderByRaw('COALESCE(starts_at, due_date) asc')
            ->limit(5)
            ->get();

        return [
            'count' => (clone $query)->count(),
            'events' => $events,
        ];
    }
}
