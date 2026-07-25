<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use App\Services\TenantContext;

class CalendarWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'calendar';
    }

    public function subscriptionModule(): ?string
    {
        return 'calendar';
    }

    public function permissionSlug(): ?string
    {
        return 'tasks.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $events = Task::query()
            ->whereNotNull('due_at')
            ->where('due_at', '>=', now()->startOfDay())
            ->where('due_at', '<=', now()->addDays(14)->endOfDay())
            ->orderBy('due_at')
            ->limit(10)
            ->get(['id', 'title', 'due_at', 'status'])
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'due_at' => $task->due_at?->toIso8601String(),
                'status' => $task->status,
            ]);

        return ['events' => $events];
    }
}
