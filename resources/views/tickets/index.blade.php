@php
    $statusVariant = [
        'open' => 'info',
        'pending' => 'warning',
        'resolved' => 'success',
        'closed' => 'neutral',
    ];
    $priorityVariant = [
        'low' => 'neutral',
        'medium' => 'info',
        'high' => 'warning',
        'urgent' => 'danger',
    ];
    $columns = [
        __('Ticket'),
        __('Customer'),
        __('Status'),
        __('Priority'),
        ['label' => __('Assignee'), 'class' => 'hidden md:table-cell'],
        ['label' => __('SLA'), 'class' => 'hidden lg:table-cell'],
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Tickets')"
        :subtitle="__('Customer support tickets')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => __('Tickets'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('customers.index')" variant="primary" size="sm">{{ __('New ticket') }}</x-ui.button>
        </x-slot:actions>

        <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-6">
            <x-ui.stat-card :label="__('Open')" :value="(string) $metrics['open']" />
            <x-ui.stat-card :label="__('Pending')" :value="(string) $metrics['pending']" />
            <x-ui.stat-card :label="__('Overdue')" :value="(string) $metrics['overdue']" />
            <x-ui.stat-card :label="__('Unassigned')" :value="(string) $metrics['unassigned']" />
            <x-ui.stat-card :label="__('Resolved')" :value="(string) $metrics['resolved']" />
            <x-ui.stat-card :label="__('Closed')" :value="(string) $metrics['closed']" />
        </div>

        <x-slot:filters>
            <form method="GET" action="{{ route('tickets.index') }}" id="tickets-index-filters" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                <div class="sm:col-span-2">
                    <x-forms.input name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search number, subject, or customer…') }}" />
                </div>
                <x-forms.select name="status" aria-label="{{ __('Status') }}">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (config('customer_tickets.statuses') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.select name="priority" aria-label="{{ __('Priority') }}">
                    <option value="">{{ __('All priorities') }}</option>
                    @foreach (config('customer_tickets.priorities') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['priority'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.select name="customer_id" aria-label="{{ __('Customer') }}">
                    <option value="">{{ __('All customers') }}</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(($filters['customer_id'] ?? '') == $customer->id)>{{ $customer->display_name }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.select name="assigned_to" aria-label="{{ __('Assignee') }}">
                    <option value="">{{ __('Anyone') }}</option>
                    @foreach ($assignees as $member)
                        <option value="{{ $member->id }}" @selected(($filters['assigned_to'] ?? '') == $member->id)>{{ $member->name }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.select name="overdue" aria-label="{{ __('SLA') }}">
                    <option value="">{{ __('All SLA') }}</option>
                    <option value="1" @selected(($filters['overdue'] ?? '') == '1')>{{ __('Overdue only') }}</option>
                </x-forms.select>
                <x-forms.select name="sort" aria-label="{{ __('Sort') }}">
                    <option value="created_at" @selected(($filters['sort'] ?? 'created_at') === 'created_at')>{{ __('Created') }}</option>
                    <option value="due_at" @selected(($filters['sort'] ?? '') === 'due_at')>{{ __('Due date') }}</option>
                    <option value="priority" @selected(($filters['sort'] ?? '') === 'priority')>{{ __('Priority') }}</option>
                    <option value="status" @selected(($filters['sort'] ?? '') === 'status')>{{ __('Status') }}</option>
                    <option value="subject" @selected(($filters['sort'] ?? '') === 'subject')>{{ __('Subject') }}</option>
                </x-forms.select>
                <x-forms.select name="sort_direction" aria-label="{{ __('Sort direction') }}">
                    <option value="desc" @selected(($filters['sort_direction'] ?? 'desc') === 'desc')>{{ __('Desc') }}</option>
                    <option value="asc" @selected(($filters['sort_direction'] ?? '') === 'asc')>{{ __('Asc') }}</option>
                </x-forms.select>
                <div class="flex gap-2">
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
                @include('metadata-fields._saved_filter_controls')
            </form>
            @include('metadata-fields._filter_chips', [
                'chipFilters' => $filters,
                'chipRoute' => 'tickets.index',
                'assignees' => $assignees,
            ])
        </x-slot:filters>

        @if ($tickets->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset
                    variant="tasks"
                    :title="__('No tickets yet')"
                    :description="__('Open a customer record to create a ticket.')"
                    :action-href="route('customers.index')"
                    :action-label="__('Open customers')"
                />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" sticky>
                @foreach ($tickets as $ticket)
                    <tr class="hover:bg-surface-muted/60">
                        <td class="px-4 py-3">
                            <a href="{{ route('tickets.show', $ticket) }}" class="text-sm font-semibold text-ink-heading hover:text-primary-700">{{ $ticket->subject }}</a>
                            <p class="text-xs text-ink-muted">{{ $ticket->number }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if ($ticket->customer)
                                <a href="{{ route('customers.show', $ticket->customer) }}" class="text-primary-600 hover:text-primary-700">{{ $ticket->customer->display_name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3"><x-ui.badge :variant="$statusVariant[$ticket->status] ?? 'neutral'">{{ $ticket->status_label }}</x-ui.badge></td>
                        <td class="px-4 py-3"><x-ui.badge :variant="$priorityVariant[$ticket->priority] ?? 'neutral'">{{ $ticket->priority_label }}</x-ui.badge></td>
                        <td class="hidden px-4 py-3 text-sm text-ink-muted md:table-cell">{{ $ticket->assignee?->name ?? __('Unassigned') }}</td>
                        <td class="hidden px-4 py-3 text-sm lg:table-cell">
                            @if ($ticket->isOverdue())
                                <x-ui.badge variant="danger">{{ __('Overdue') }}</x-ui.badge>
                            @elseif ($ticket->due_at)
                                <span class="text-ink-muted">{{ $ticket->due_at->format('M j, Y g:i A') }}</span>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($tickets->hasPages())
            <x-slot:pagination>
                {{ $tickets->links() }}
            </x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
