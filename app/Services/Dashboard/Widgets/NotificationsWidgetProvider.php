<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\Organization;
use App\Models\User;

class NotificationsWidgetProvider extends AbstractWidgetProvider
{
    public function key(): string
    {
        return 'notifications';
    }

    public function subscriptionModule(): ?string
    {
        return 'notifications';
    }

    public function permissionSlug(): ?string
    {
        return null;
    }

    protected function fetchData(User $user, Organization $organization, array $configuration): array
    {
        $limit = (int) ($configuration['limit'] ?? 5);

        $notifications = $user->notifications()
            ->where('data->organization_id', $organization->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? '',
                'message' => $n->data['message'] ?? '',
                'action_url' => $n->data['action_url'] ?? null,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at?->toIso8601String(),
            ]);

        return [
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()
                ->where('data->organization_id', $organization->id)
                ->count(),
        ];
    }
}
