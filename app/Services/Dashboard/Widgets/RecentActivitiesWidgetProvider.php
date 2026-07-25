<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;

class RecentActivitiesWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'recent_activities';
    }

    public function subscriptionModule(): ?string
    {
        return 'common';
    }

    public function permissionSlug(): ?string
    {
        return 'audit.view';
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        app(TenantContext::class)->set($organization);

        $limit = (int) ($configuration['limit'] ?? 10);

        $activities = AuditLog::query()
            ->with('user:id,name')
            ->latest()
            ->limit($limit)
            ->get(['id', 'event', 'subject', 'user_id', 'created_at'])
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'event' => $log->event,
                'subject' => $log->subject,
                'user_name' => $log->user?->name,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return ['activities' => $activities];
    }
}
