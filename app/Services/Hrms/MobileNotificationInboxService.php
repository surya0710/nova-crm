<?php

namespace App\Services\Hrms;

use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Mobile notification inbox helpers. Persistence remains Laravel notifications.
 */
class MobileNotificationInboxService
{
    public function __construct(
        protected TenantContext $tenant,
    ) {}

    public function unreadCount(User $user): int
    {
        $organization = $this->tenant->get();

        return $user->unreadNotifications()
            ->when($organization, fn ($q) => $q->where('data->organization_id', $organization->id))
            ->count();
    }

    public function paginate(User $user, int $perPage = 20): LengthAwarePaginator
    {
        $organization = $this->tenant->get();

        return $user->notifications()
            ->when($organization, fn ($q) => $q->where('data->organization_id', $organization->id))
            ->latest()
            ->paginate($perPage);
    }

    public function markRead(User $user, string $notificationId): DatabaseNotification
    {
        /** @var DatabaseNotification $item */
        $item = $user->notifications()->where('id', $notificationId)->firstOrFail();
        $item->markAsRead();

        return $item;
    }

    public function markAllRead(User $user): int
    {
        $organization = $this->tenant->get();

        return $user->unreadNotifications()
            ->when($organization, fn ($q) => $q->where('data->organization_id', $organization->id))
            ->update(['read_at' => now()]);
    }
}
