<?php

namespace App\Services;

use App\Events\TicketAssigned;
use App\Events\TicketCreated;
use App\Events\TicketEscalated;
use App\Events\TicketStatusChanged;
use App\Models\Customer;
use App\Models\CustomerTicket;
use App\Models\CustomerTicketNote;
use App\Models\User;
use App\Workflow\WorkflowRuntimeContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerTicketService
{
    public const SORTABLE = ['created_at', 'updated_at', 'due_at', 'priority', 'status', 'subject'];

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Customer $customer, array $data, User $user): CustomerTicket
    {
        return DB::transaction(function () use ($customer, $data, $user) {
            $priority = $data['priority'] ?? 'medium';
            $status = $data['status'] ?? 'open';
            $slaHours = $data['sla_hours'] ?? $this->slaHoursFor($priority);

            $ticket = CustomerTicket::query()->create([
                ...$data,
                'organization_id' => $customer->organization_id,
                'customer_id' => $customer->id,
                'created_by' => $user->id,
                'priority' => $priority,
                'status' => $status,
                'sla_hours' => $slaHours,
                'due_at' => $data['due_at'] ?? now()->addHours($slaHours),
                'resolved_at' => in_array($status, ['resolved', 'closed'], true) ? now() : null,
                'closed_at' => $status === 'closed' ? now() : null,
            ]);

            if (! $ticket->number) {
                $ticket->update([
                    'number' => 'TKT-'.str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT),
                ]);
            }

            app(CustomerService::class)->touchLastActivity($customer);
            $ticket = $ticket->fresh();
            event(TicketCreated::forModel($ticket, ['actor_id' => $user->id]));

            if ($ticket->assigned_to) {
                event(TicketAssigned::forModel($ticket, [
                    'actor_id' => $user->id,
                    'assigned_to' => $ticket->assigned_to,
                ]));
            }

            return $ticket;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CustomerTicket $ticket, array $data, ?User $actor = null): CustomerTicket
    {
        $previousStatus = $ticket->status;
        $previousAssignee = $ticket->assigned_to;
        $previousPriority = $ticket->priority;

        if (array_key_exists('priority', $data) && $data['priority'] !== $ticket->priority && ! array_key_exists('due_at', $data)) {
            $data['sla_hours'] = $this->slaHoursFor((string) $data['priority']);
            $data['due_at'] = $ticket->created_at?->copy()->addHours($data['sla_hours']) ?? now()->addHours($data['sla_hours']);
        }

        if (array_key_exists('status', $data) && $data['status'] !== $previousStatus) {
            $this->assertTransition($ticket, (string) $data['status']);
            $data = array_merge($data, $this->statusTimestamps($ticket, (string) $data['status']));
        }

        $ticket->update($data);
        $ticket = $ticket->fresh();
        $actorId = (int) ($actor?->id ?? $ticket->created_by);
        $runtime = app(WorkflowRuntimeContext::class);

        if ($ticket->status !== $previousStatus) {
            event(TicketStatusChanged::forModel($ticket, [
                'actor_id' => $actorId,
                'from' => $previousStatus,
                'to' => $ticket->status,
            ], causationId: $runtime->causationId, depth: $runtime->causationId ? $runtime->depth + 1 : 0));
        }

        if ($ticket->assigned_to !== $previousAssignee) {
            event(TicketAssigned::forModel($ticket, [
                'actor_id' => $actorId,
                'from' => $previousAssignee,
                'assigned_to' => $ticket->assigned_to,
            ], causationId: $runtime->causationId, depth: $runtime->causationId ? $runtime->depth + 1 : 0));
        }

        if ($this->priorityEscalated((string) $previousPriority, (string) $ticket->priority)) {
            event(TicketEscalated::forModel($ticket, [
                'actor_id' => $actorId,
                'from' => $previousPriority,
                'to' => $ticket->priority,
            ], causationId: $runtime->causationId, depth: $runtime->causationId ? $runtime->depth + 1 : 0));
        }

        return $ticket;
    }

    public function assign(CustomerTicket $ticket, ?int $userId, User $actor): CustomerTicket
    {
        return $this->update($ticket, ['assigned_to' => $userId], $actor);
    }

    public function reopen(CustomerTicket $ticket, User $actor): CustomerTicket
    {
        if (! $ticket->canReopen()) {
            throw ValidationException::withMessages([
                'status' => __('Only resolved or closed tickets can be reopened.'),
            ]);
        }

        return $this->update($ticket, ['status' => 'open'], $actor);
    }

    public function transition(CustomerTicket $ticket, string $status, User $actor): CustomerTicket
    {
        return $this->update($ticket, ['status' => $status], $actor);
    }

    public function addNote(CustomerTicket $ticket, string $body, User $actor): CustomerTicketNote
    {
        if (trim($body) === '') {
            throw ValidationException::withMessages(['body' => __('A note body is required.')]);
        }

        $note = CustomerTicketNote::query()->create([
            'organization_id' => $ticket->organization_id,
            'customer_ticket_id' => $ticket->id,
            'user_id' => $actor->id,
            'body' => $body,
        ]);

        if ($ticket->first_response_at === null) {
            $ticket->update(['first_response_at' => now()]);
        }

        app(CustomerService::class)->touchLastActivity($ticket->customer);

        return $note;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function applyIndexFilters(Builder $query, array $filters): Builder
    {
        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $like = '%'.$search.'%';
            $query->where(function (Builder $inner) use ($like) {
                $inner->where('subject', 'like', $like)
                    ->orWhere('number', 'like', $like)
                    ->orWhere('body', 'like', $like)
                    ->orWhereHas('customer', function (Builder $customer) use ($like) {
                        $customer->where('company', 'like', $like)->orWhere('name', 'like', $like);
                    });
            });
        }

        foreach (['status', 'priority'] as $field) {
            if (filled($filters[$field] ?? null)) {
                $query->where($field, $filters[$field]);
            }
        }

        foreach (['customer_id', 'contact_id', 'assigned_to'] as $field) {
            if ((int) ($filters[$field] ?? 0) > 0) {
                $query->where($field, (int) $filters[$field]);
            }
        }

        if (($filters['overdue'] ?? null) === '1' || ($filters['overdue'] ?? null) === true) {
            $query->overdue();
        }

        if (($filters['unassigned'] ?? null) === '1' || ($filters['unassigned'] ?? null) === true) {
            $query->whereNull('assigned_to');
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function applyIndexSort(Builder $query, array $filters): Builder
    {
        $sort = (string) ($filters['sort'] ?? 'created_at');
        if (! in_array($sort, self::SORTABLE, true)) {
            $sort = 'created_at';
        }

        $direction = ($filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if ($sort === 'priority') {
            return $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low') ".($direction === 'asc' ? 'DESC' : 'ASC'));
        }

        return $query->orderBy($sort, $direction);
    }

    /**
     * @return array<string, int>
     */
    public function metrics(): array
    {
        $open = CustomerTicket::query()->open();

        return [
            'open' => (clone $open)->where('status', 'open')->count(),
            'pending' => (clone $open)->where('status', 'pending')->count(),
            'overdue' => CustomerTicket::query()->overdue()->count(),
            'unassigned' => (clone $open)->whereNull('assigned_to')->count(),
            'resolved' => CustomerTicket::query()->where('status', 'resolved')->count(),
            'closed' => CustomerTicket::query()->where('status', 'closed')->count(),
        ];
    }

    protected function priorityEscalated(string $from, string $to): bool
    {
        if ($from === $to) {
            return false;
        }

        $rank = ['low' => 1, 'medium' => 2, 'high' => 3, 'urgent' => 4];

        return ($rank[$to] ?? 0) >= 3 && ($rank[$to] ?? 0) > ($rank[$from] ?? 0);
    }

    protected function slaHoursFor(string $priority): int
    {
        return (int) config('customer_tickets.sla_hours.'.$priority, 24);
    }

    protected function assertTransition(CustomerTicket $ticket, string $status): void
    {
        if ($status === $ticket->status) {
            return;
        }

        $allowed = $ticket->allowedTransitions();
        if (! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => __('Tickets cannot move from :from to :to.', [
                    'from' => $ticket->status_label,
                    'to' => config('customer_tickets.statuses.'.$status, $status),
                ]),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function statusTimestamps(CustomerTicket $ticket, string $status): array
    {
        return match ($status) {
            'open', 'pending' => [
                'resolved_at' => null,
                'closed_at' => null,
            ],
            'resolved' => [
                'resolved_at' => $ticket->resolved_at ?? now(),
                'closed_at' => null,
            ],
            'closed' => [
                'resolved_at' => $ticket->resolved_at ?? now(),
                'closed_at' => now(),
            ],
            default => [],
        };
    }
}
