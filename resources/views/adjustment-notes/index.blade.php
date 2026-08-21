@php
    $term = $type === 'debit' ? 'debit_notes' : 'credit_notes';
    $statusVariant = [
        'draft' => 'neutral',
        'issued' => 'info',
        'applied' => 'success',
        'cancelled' => 'danger',
    ];
    $indexRoute = $type === 'debit' ? 'debit-notes.index' : 'credit-notes.index';
    $createRoute = $type === 'debit' ? 'debit-notes.create' : 'credit-notes.create';
    $showRoute = $type === 'debit' ? 'debit-notes.show' : 'credit-notes.show';
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="crm_term($term)"
        :subtitle="$type === 'debit' ? __('Increase amounts owed on issued invoices') : __('Reduce amounts owed on issued invoices')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term($term), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('create', App\Models\AdjustmentNote::class)
                <x-ui.button :href="route($createRoute)" variant="primary" size="sm">{{ __('New :label', ['label' => crm_term($type === 'debit' ? 'debit_note' : 'credit_note')]) }}</x-ui.button>
            @endcan
        </x-slot:actions>

        <x-slot:filters>
            <form method="GET" action="{{ route($indexRoute) }}" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <x-forms.input name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search number, title, customer…') }}" />
                <x-forms.select name="status">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (config('adjustment_notes.statuses') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
            </form>
        </x-slot:filters>

        @if ($notes->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="generic" :title="__('No notes yet')" :action-href="auth()->user()->can('create', App\Models\AdjustmentNote::class) ? route($createRoute) : null" :action-label="__('Create note')" />
            </x-ui.card>
        @else
            <x-tables.table :columns="[__('Number'), __('Customer'), __('Invoice'), ['label' => __('Total'), 'align' => 'right'], __('Status')]" :sticky="false">
                @foreach ($notes as $note)
                    <tr>
                        <td class="px-4 py-3"><a href="{{ route($showRoute, $note) }}" class="font-semibold text-primary-700">{{ $note->number }}</a></td>
                        <td class="px-4 py-3 text-sm">{{ $note->customer?->display_name }}</td>
                        <td class="px-4 py-3 text-sm">{{ $note->invoice?->number ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-sm">{{ $note->formatted_total }}</td>
                        <td class="px-4 py-3"><x-ui.badge :variant="$statusVariant[$note->status] ?? 'neutral'">{{ $note->status_label }}</x-ui.badge></td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($notes->hasPages())
            <x-slot:pagination>{{ $notes->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
