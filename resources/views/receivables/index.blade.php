<x-app-layout>
    <x-flash-messages />
    <x-layouts.entity-listing :title="__('Receivables')" :subtitle="__('Outstanding balances, aging, and collection status')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => __('Receivables'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
            <x-ui.stat-card :label="__('Outstanding')" :value="number_format((float) $metrics['outstanding_receivables'], 2).' '.($organization->currency ?? '')" />
            <x-ui.stat-card :label="__('Unpaid')" :value="(string) $statusCounts['unpaid']" />
            <x-ui.stat-card :label="__('Partial')" :value="(string) $statusCounts['partial']" />
            <x-ui.stat-card :label="__('Overdue')" :value="(string) $statusCounts['overdue']" />
        </div>

        <x-entity.section :title="__('Aging')" class="mb-6">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                @foreach ($aging as $key => $bucket)
                    <div class="rounded-lg border border-line p-3">
                        <div class="text-xs text-ink-muted">{{ $bucket['label'] }}</div>
                        <div class="mt-1 font-semibold">{{ number_format($bucket['total'], 2) }}</div>
                        <div class="text-xs text-ink-muted">{{ $bucket['count'] }}</div>
                    </div>
                @endforeach
            </div>
        </x-entity.section>

        <x-slot:filters>
            <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <x-forms.select name="customer_id">
                    <option value="">{{ __('All customers') }}</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) ($filters['customer_id'] ?? '') === (string) $customer->id)>{{ $customer->display_name }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.select name="collection_status">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (['unpaid' => __('Unpaid'), 'partial' => __('Partial'), 'overdue' => __('Overdue'), 'paid' => __('Paid')] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.select name="aging">
                    <option value="">{{ __('All aging') }}</option>
                    @foreach (App\Services\RevenueService::AGING_BUCKETS as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['aging'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
            </form>
        </x-slot:filters>

        <x-tables.table :columns="[__('Invoice'), __('Customer'), __('Due'), __('Status'), ['label' => __('Outstanding'), 'align' => 'right'], __('Aging')]" :sticky="false">
            @forelse ($invoices as $invoice)
                <tr>
                    <td class="px-4 py-3"><a class="font-semibold text-primary-700" href="{{ route('invoices.show', $invoice) }}">{{ $invoice->number }}</a></td>
                    <td class="px-4 py-3 text-sm">{{ $invoice->customer?->display_name }}</td>
                    <td class="px-4 py-3 text-sm">{{ $invoice->due_date?->format('M j, Y') ?? '—' }}</td>
                    <td class="px-4 py-3"><x-ui.badge :variant="$invoice->collection_status === 'overdue' ? 'danger' : ($invoice->collection_status === 'partial' ? 'warning' : 'neutral')">{{ ucfirst($invoice->collection_status) }}</x-ui.badge></td>
                    <td class="px-4 py-3 text-right text-sm">{{ number_format($invoice->effective_balance, 2) }} {{ $invoice->currency }}</td>
                    <td class="px-4 py-3 text-sm">{{ App\Services\RevenueService::AGING_BUCKETS[$invoice->agingBucket()] ?? $invoice->agingBucket() }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-ink-muted">{{ __('No outstanding invoices.') }}</td></tr>
            @endforelse
        </x-tables.table>
    </x-layouts.entity-listing>
</x-app-layout>
