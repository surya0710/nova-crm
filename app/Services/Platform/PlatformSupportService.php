<?php

namespace App\Services\Platform;

use App\Models\PlatformAnnouncement;
use App\Models\PlatformSupportTicket;
use App\Models\PlatformUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PlatformSupportService
{
    public function __construct(
        protected PlatformAuditService $audit,
    ) {}

    public function tickets(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PlatformSupportTicket::query()
            ->with(['organization:id,name', 'assignee:id,name', 'creator:id,name'])
            ->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('requester_email', 'like', "%{$search}%")
                    ->orWhere('requester_name', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function createTicket(array $data, PlatformUser $actor): PlatformSupportTicket
    {
        $ticket = PlatformSupportTicket::create([
            'organization_id' => $data['organization_id'] ?? null,
            'platform_user_id' => $actor->id,
            'assignee_id' => $data['assignee_id'] ?? null,
            'subject' => $data['subject'],
            'body' => $data['body'] ?? null,
            'status' => $data['status'] ?? 'open',
            'priority' => $data['priority'] ?? 'normal',
            'category' => $data['category'] ?? 'general',
            'requester_name' => $data['requester_name'] ?? null,
            'requester_email' => $data['requester_email'] ?? null,
        ]);

        $this->audit->log('support.ticket_created', $actor, $ticket->organization, [
            'ticket_id' => $ticket->id,
            'subject' => $ticket->subject,
        ]);

        return $ticket;
    }

    public function updateTicket(PlatformSupportTicket $ticket, array $data, PlatformUser $actor): PlatformSupportTicket
    {
        if (($data['status'] ?? null) === 'resolved' && ! $ticket->resolved_at) {
            $data['resolved_at'] = now();
        }

        $ticket->update(collect($data)->only([
            'assignee_id', 'subject', 'body', 'status', 'priority', 'category', 'resolved_at',
        ])->all());

        $this->audit->log('support.ticket_updated', $actor, $ticket->organization, [
            'ticket_id' => $ticket->id,
            'status' => $ticket->status,
        ]);

        return $ticket->fresh();
    }

    public function announcements(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PlatformAnnouncement::query()
            ->with('author:id,name')
            ->latest();

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function createAnnouncement(array $data, PlatformUser $actor): PlatformAnnouncement
    {
        $announcement = PlatformAnnouncement::create([
            'created_by' => $actor->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'type' => $data['type'] ?? 'announcement',
            'status' => $data['status'] ?? 'draft',
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'broadcast' => (bool) ($data['broadcast'] ?? false),
        ]);

        $this->audit->log('support.announcement_created', $actor, null, [
            'announcement_id' => $announcement->id,
            'type' => $announcement->type,
        ]);

        return $announcement;
    }

    public function overview(): array
    {
        return [
            'open_tickets' => PlatformSupportTicket::query()->whereIn('status', ['open', 'in_progress'])->count(),
            'resolved_tickets' => PlatformSupportTicket::query()->where('status', 'resolved')->count(),
            'maintenance' => PlatformAnnouncement::query()->where('type', 'maintenance')->where('status', 'published')->count(),
            'broadcasts' => PlatformAnnouncement::query()->where('broadcast', true)->where('status', 'published')->count(),
        ];
    }
}
