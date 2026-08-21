<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Communications')"
        :subtitle="__('CRM email conversations and delivery status')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => __('Communications'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:filters>
            <form method="GET" action="{{ route('crm.communications.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <x-forms.input name="q" :value="$filters['q'] ?? ''" placeholder="{{ __('Search subject, customer, contact…') }}" />
                <x-forms.select name="status" aria-label="{{ __('Status') }}">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
            </form>
        </x-slot:filters>

        @if ($conversations->isEmpty())
            <x-ui.card>
                <p class="py-8 text-center text-sm text-ink-muted">{{ __('No email conversations yet. Send a message from a customer, contact, deal, or ticket.') }}</p>
            </x-ui.card>
        @else
            <x-tables.table :columns="[__('Subject'), __('Customer'), __('Contact'), __('Status'), __('Last message'), __('Actions')]" sticky>
                @foreach ($conversations as $conversation)
                    <tr class="hover:bg-surface-muted/60">
                        <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $conversation->subject }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">
                            @if ($conversation->customer)
                                <a href="{{ route('customers.show', $conversation->customer) }}" class="text-primary-600 hover:text-primary-700">{{ $conversation->customer->display_name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $conversation->contact?->name ?? '—' }}</td>
                        <td class="px-4 py-3"><x-ui.badge variant="neutral">{{ $conversation->lastStatusLabel() }}</x-ui.badge></td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $conversation->last_message_at?->format('M j, Y g:i A') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('crm.communications.show', $conversation) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('Open') }}</a>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
            <div class="mt-4">{{ $conversations->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
