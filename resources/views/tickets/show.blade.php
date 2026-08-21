@php
    $statusVariant = [
        'open' => 'info',
        'pending' => 'warning',
        'resolved' => 'success',
        'closed' => 'neutral',
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$ticket->subject"
        :subtitle="$ticket->number"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => __('Tickets'), 'href' => route('tickets.index')],
                ['label' => $ticket->customer->display_name, 'href' => route('customers.show', $ticket->customer)],
                ['label' => $ticket->number, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('update', $ticket)
                <x-ui.button :href="route('tickets.show', $ticket).'#email-composer'" variant="secondary" size="sm">{{ __('Email') }}</x-ui.button>
                @if ($ticket->canReopen())
                    <form method="POST" action="{{ route('tickets.reopen', $ticket) }}">
                        @csrf
                        <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Reopen') }}</x-ui.button>
                    </form>
                @endif
                <x-ui.button :href="route('tickets.edit', $ticket)" variant="secondary" size="sm">{{ __('Edit') }}</x-ui.button>
            @endcan
            @can('delete', $ticket)
                <form method="POST" action="{{ route('tickets.destroy', $ticket) }}" onsubmit="return confirm('{{ __('Delete this ticket?') }}')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger" size="sm">{{ __('Delete') }}</x-ui.button>
                </form>
            @endcan
        </x-slot:actions>

        <x-slot:tabs>
            <x-ui.badge :variant="$statusVariant[$ticket->status] ?? 'neutral'">{{ $ticket->status_label }}</x-ui.badge>
            <x-ui.badge variant="neutral">{{ $ticket->priority_label }}</x-ui.badge>
            @if ($ticket->isOverdue())
                <x-ui.badge variant="danger">{{ __('Overdue') }}</x-ui.badge>
            @endif
        </x-slot:tabs>

        <x-entity.section :title="__('Details')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Company')">
                    <a href="{{ route('customers.show', $ticket->customer) }}" class="text-primary-600 hover:text-primary-700">{{ $ticket->customer->display_name }}</a>
                </x-entity.definition-item>
                <x-entity.definition-item :label="__('Contact')">
                    @if ($ticket->contact)
                        <a href="{{ route('contacts.show', $ticket->contact) }}" class="text-primary-600 hover:text-primary-700">{{ $ticket->contact->name }}</a>
                    @else
                        —
                    @endif
                </x-entity.definition-item>
                <x-entity.definition-item :label="__('Assignee')">{{ $ticket->assignee?->name ?? __('Unassigned') }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Due')">
                    {{ $ticket->due_at?->format('M j, Y g:i A') ?? '—' }}
                    @if ($ticket->sla_hours)
                        <span class="text-ink-muted">({{ __(':hours h SLA', ['hours' => $ticket->sla_hours]) }})</span>
                    @endif
                </x-entity.definition-item>
            </x-entity.definition-list>
            @can('update', $ticket)
                <div class="mt-4 flex flex-wrap items-end gap-3">
                    <form method="POST" action="{{ route('tickets.assign', $ticket) }}" class="flex flex-wrap items-end gap-2">
                        @csrf
                        @method('PATCH')
                        <x-forms.field :label="__('Reassign')" name="assigned_to">
                            <x-forms.select id="assigned_to" name="assigned_to">
                                <option value="">{{ __('Unassigned') }}</option>
                                @foreach ($assignees as $member)
                                    <option value="{{ $member->id }}" @selected($ticket->assigned_to == $member->id)>{{ $member->name }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                        <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Assign') }}</x-ui.button>
                    </form>
                    @foreach ($ticket->allowedTransitions() as $next)
                        <form method="POST" action="{{ route('tickets.status', $ticket) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $next }}">
                            <x-ui.button type="submit" variant="ghost" size="sm">{{ config('customer_tickets.statuses.'.$next) }}</x-ui.button>
                        </form>
                    @endforeach
                </div>
            @endcan
            @if ($ticket->body)
                <p class="mt-4 whitespace-pre-wrap text-sm text-ink">{{ $ticket->body }}</p>
            @endif
        </x-entity.section>

        <x-entity.section :title="__('Timeline')">
            <x-activity.timeline
                :empty="$ticket->notes->isEmpty()"
                :empty-title="__('No updates yet')"
                :empty-description="__('Add a note when you follow up.')"
            >
                @can('update', $ticket)
                    <x-slot:composer>
                        <form method="POST" action="{{ route('tickets.notes.store', $ticket) }}" class="space-y-3">
                            @csrf
                            <x-forms.field :label="__('Add a note')" name="body" required>
                                <x-forms.textarea id="body" name="body" rows="3" required>{{ old('body') }}</x-forms.textarea>
                            </x-forms.field>
                            <div class="flex justify-end">
                                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add note') }}</x-ui.button>
                            </div>
                        </form>
                    </x-slot:composer>
                @endcan
                @foreach ($ticket->notes as $note)
                    <x-activity.timeline-item :actor="$note->user?->name" :timestamp="$note->created_at">
                        <x-slot:label>{{ __('Note') }}</x-slot:label>
                        {{ $note->body }}
                    </x-activity.timeline-item>
                @endforeach
            </x-activity.timeline>
        </x-entity.section>

        <x-crm-email-conversations :related="$ticket" />

        @can('update', $ticket)
            <x-client-email-form
                :action="route('tickets.send', $ticket)"
                :email="old('email', $ticket->contact?->email ?? $ticket->customer->email)"
                :submit-label="__('Send Email')"
                :title="__('Email about this ticket')"
                :organization="$organization ?? $ticket->organization"
                :show-subject="true"
                :subject="old('subject', $ticket->subject)"
                :missing-email-hint="! ($ticket->contact?->email || $ticket->customer->email)"
                :show-bcc="true"
                module="tickets"
                :related="$ticket"
                :suggested-recipients="collect([$ticket->contact?->email, $ticket->customer->email])->filter()->unique()->values()"
            />
        @endcan
    </x-layouts.entity-detail>
</x-app-layout>
