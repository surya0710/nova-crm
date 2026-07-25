<x-platform-layout>
    <x-layouts.entity-listing
        :title="__('Transactions')"
        :subtitle="__('Payment and billing transactions across organizations')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Subscriptions'), 'href' => route('platform.subscriptions.index')],
                ['label' => __('Transactions'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:filters>
            <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <x-forms.field :label="__('Search')" name="search" class="sm:col-span-2">
                    <x-forms.input type="search" name="search" value="{{ $filters['search'] ?? '' }}" />
                </x-forms.field>
                <x-forms.field :label="__('Status')" name="status">
                    <x-forms.select name="status">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (['succeeded', 'pending', 'failed', 'refunded'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <div class="flex items-end gap-2">
                    <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($transactions->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="generic" :title="__('No transactions')" :description="__('Billing transactions will appear here.')" /></x-ui.card>
        @else
            <x-ui.card :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-surface-muted/50 text-ink-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Number') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Organization') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Description') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Amount') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Status') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($transactions as $transaction)
                                <tr class="hover:bg-surface-muted/60">
                                    <td class="px-4 py-3 font-medium text-ink-heading">{{ $transaction->number }}</td>
                                    <td class="px-4 py-3 text-ink">{{ $transaction->organization?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $transaction->description ?? '—' }}</td>
                                    <td class="px-4 py-3 text-ink">{{ $transaction->currency }} {{ number_format($transaction->amount, 2) }}</td>
                                    <td class="px-4 py-3">
                                        <x-ui.badge :variant="$transaction->status === 'succeeded' ? 'success' : ($transaction->status === 'failed' ? 'danger' : 'neutral')">
                                            {{ ucfirst($transaction->status) }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $transaction->occurred_at?->format('M j, Y H:i') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
            <div class="mt-4">{{ $transactions->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-platform-layout>
