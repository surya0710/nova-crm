<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\TaskTimeLog;
use App\Models\User;
use App\Services\TenantContext;

class TimeLoggedTodayWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'time_logged_today';
    }

    public function subscriptionModule(): ?string
    {
        return 'projects';
    }

    public function permissionSlug(): ?string
    {
        return 'tasks.time-log';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $minutes = (int) TaskTimeLog::query()
            ->where('user_id', $user->id)
            ->whereNotNull('end_time')
            ->whereDate('start_time', today())
            ->sum('duration_minutes');

        $entries = TaskTimeLog::query()
            ->where('user_id', $user->id)
            ->whereNotNull('end_time')
            ->whereDate('start_time', today())
            ->with(['task:id,title'])
            ->latest('start_time')
            ->limit(5)
            ->get(['id', 'task_id', 'duration_minutes', 'description', 'start_time']);

        return [
            'total_minutes' => $minutes,
            'total_hours' => round($minutes / 60, 2),
            'entries' => $entries,
        ];
    }
}
