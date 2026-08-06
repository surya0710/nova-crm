<?php

namespace App\Services;

use App\Models\ClientNotification;
use App\Models\ClientUser;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;

class PortalNotificationService
{
    public function __construct(protected NotificationService $notifications) {}

    public function notify(ClientUser $client, string $title, ?string $message = null, ?string $actionUrl = null): ClientNotification
    {
        return ClientNotification::query()->create([
            'organization_id' => $client->organization_id,
            'client_user_id' => $client->id,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
        ]);
    }

    /**
     * @param  Collection<int, ClientUser>|iterable<ClientUser>  $clients
     */
    public function notifyMany(iterable $clients, string $title, ?string $message = null, ?string $actionUrl = null): void
    {
        foreach ($clients as $client) {
            $this->notify($client, $title, $message, $actionUrl);
        }
    }

    public function notifyProjectClients(Organization $organization, int $projectId, string $title, ?string $message = null, ?string $actionUrl = null): void
    {
        $clients = ClientUser::query()
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->whereHas('projectAccess', fn ($q) => $q->where('project_id', $projectId))
            ->get();

        $this->notifyMany($clients, $title, $message, $actionUrl);
    }

    public function notifyStaff(int $organizationId, User $user, string $title, string $message, ?string $actionUrl = null): void
    {
        $this->notifications->send($organizationId, (int) $user->id, $title, $message, $actionUrl);
    }

    public function markRead(ClientNotification $notification): ClientNotification
    {
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return $notification->fresh();
    }
}
