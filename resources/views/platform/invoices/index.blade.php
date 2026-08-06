<x-platform-layout>
    <x-layouts.entity-listing
        :title="__('Invoices')"
        :subtitle="__('Platform billing invoices across organizations')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Subscriptions'), 'href' => route('platform.subscriptions.index')],
                ['label' => __('Invoices'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:filters>
            <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <x-forms.field :label="__('Search')" name="search" class="sm:col-span-2">
                    <x-forms.input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Number, organization…') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Status')" name="status">
                    <x-forms.select name="status">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (['open', 'pending', 'paid', 'failed', 'void'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <div class="flex items-end gap-2">
                    <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($invoices->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="generic" :title="__('No invoices')" :description="__('Billing invoices will appear here.')" /></x-ui.card>
        @else
            <x-ui.card :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-surface-muted/50 text-ink-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Number') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Organization') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Amount') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Status') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($invoices as $invoice)
                                <tr class="hover:bg-surface-muted/60">
                                    <td class="px-4 py-3 font-medium text-ink-heading">{{ $invoice->number }}</td>
                                    <td class="px-4 py-3 text-ink">{{ $invoice->organization?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-ink">{{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}</td>
                                    <td class="px-4 py-3">
                                        <x-ui.badge variant="neutral">{{ ucfirst($invoice->status) }}</x-ui.badge>
                                    </td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $invoice->occurred_at?->format('M j, Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
            <div class="mt-4">{{ $invoices->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-platform-layout>
