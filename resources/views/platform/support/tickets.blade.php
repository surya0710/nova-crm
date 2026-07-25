@php $canManage = auth('platform')->user()->hasPermission('platform.support.manage'); @endphp

<x-platform-layout>
    <x-layouts.entity-listing
        :title="__('Support Tickets')"
        :subtitle="__('Track and resolve customer support requests')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Support'), 'href' => route('platform.support.index')],
                ['label' => __('Tickets'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @if ($canManage)
                <x-ui.button :href="route('platform.support.tickets.create')" variant="primary" size="sm">{{ __('New Ticket') }}</x-ui.button>
            @endif
        </x-slot:actions>

        <x-slot:filters>
            <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-5">
                <x-forms.field :label="__('Search')" name="search" class="sm:col-span-2">
                    <x-forms.input type="search" name="search" value="{{ $filters['search'] ?? '' }}" />
                </x-forms.field>
                <x-forms.field :label="__('Status')" name="status">
                    <x-forms.select name="status">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (['open', 'in_progress', 'resolved', 'closed'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Priority')" name="priority">
                    <x-forms.select name="priority">
                        <option value="">{{ __('All priorities') }}</option>
                        @foreach (['low', 'normal', 'high', 'urgent'] as $priority)
                            <option value="{{ $priority }}" @selected(($filters['priority'] ?? '') === $priority)>{{ ucfirst($priority) }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <div class="flex items-end gap-2">
                    <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($tickets->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="tickets" :action-href="$canManage ? route('platform.support.tickets.create') : null" />
            </x-ui.card>
        @else
            <x-ui.card :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-surface-muted/50 text-ink-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Subject') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Organization') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Status') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Priority') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Assignee') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Updated') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($tickets as $ticket)
                                <tr class="hover:bg-surface-muted/60">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('platform.support.tickets.show', $ticket) }}" class="font-medium text-ink-heading hover:text-primary-700">
                                            {{ $ticket->subject }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $ticket->organization?->name ?? '—' }}</td>
                                    <td class="px-4 py-3"><x-ui.badge variant="neutral">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</x-ui.badge></td>
                                    <td class="px-4 py-3"><x-ui.badge :variant="$ticket->priority === 'urgent' || $ticket->priority === 'high' ? 'warning' : 'neutral'">{{ ucfirst($ticket->priority) }}</x-ui.badge></td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $ticket->assignee?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $ticket->updated_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
            <div class="mt-4">{{ $tickets->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-platform-layout>
