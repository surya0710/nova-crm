@php
    $statusVariant = [
        'draft' => 'neutral',
        'sent' => 'info',
        'accepted' => 'success',
        'rejected' => 'danger',
        'expired' => 'warning',
        'converted' => 'primary',
    ];
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Quotation'),
        __('Customer'),
        ['label' => __('Issue Date'), 'class' => 'hidden md:table-cell'],
        ['label' => __('Total'), 'align' => 'right'],
        __('Status'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="crm_term('quotations')"
        :subtitle="__('Create and manage sales quotations')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('quotations'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('create', App\Models\Quotation::class)
                <x-ui.button :href="route('quotations.create')" variant="primary" size="sm">{{ __('New Quotation') }}</x-ui.button>
            @endcan
        </x-slot:actions>

        <x-slot:filters>
            <form method="GET" action="{{ route('quotations.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <label for="quotations-search" class="sr-only">{{ __('Search quotations') }}</label>
                    <x-forms.input id="quotations-search" name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search number, title, customer…') }}" />
                </div>
                <x-forms.select name="status" aria-label="{{ __('Status') }}">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (config('quotations.statuses') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <div class="flex gap-2">
                    <x-forms.select name="customer_id" class="flex-1" aria-label="{{ __('Customer') }}">
                        <option value="">{{ __('All customers') }}</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) ($filters['customer_id'] ?? '') === (string) $customer->id)>{{ $customer->display_name }}</option>
                        @endforeach
                    </x-forms.select>
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($quotations->isEmpty())
            <x-ui.card>
                @if (! empty($filters['search']) || ! empty($filters['status']) || ! empty($filters['customer_id']))
                    <x-ui.empty-state-preset variant="search" />
                @else
                    <x-ui.empty-state-preset
                        variant="generic"
                        :title="__('No quotations yet')"
                        :description="__('Create your first quotation for a customer.')"
                        :action-href="auth()->user()->can('create', App\Models\Quotation::class) ? route('quotations.create') : null"
                        :action-label="__('New Quotation')"
                    />
                @endif
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($quotations as $quotation)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('quotations.show', $quotation) }}" class="group block">
                                <p class="text-sm font-semibold text-ink-heading group-hover:text-primary-700">{{ $quotation->number }}</p>
                                @if ($quotation->title)
                                    <p class="mt-0.5 text-xs text-ink-muted">{{ $quotation->title }}</p>
                                @endif
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $quotation->customer->display_name }}</td>
                        <td class="px-4 py-3 hidden md:table-cell text-sm text-ink-muted">{{ $quotation->issue_date->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-right text-sm text-ink-heading">{{ $quotation->formatted_total }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$statusVariant[$quotation->status] ?? 'neutral'">{{ $quotation->status_label }}</x-ui.badge>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($quotations->hasPages())
            <x-slot:pagination>
                {{ $quotations->links() }}
            </x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
