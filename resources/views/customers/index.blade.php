@php
    $statusVariant = [
        'prospect' => 'info',
        'active' => 'success',
        'inactive' => 'neutral',
    ];
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Customer'),
        __('Status'),
        ['label' => __('Industry'), 'class' => 'hidden md:table-cell'],
        ['label' => __('Account Manager'), 'class' => 'hidden lg:table-cell'],
        ['label' => __('Location'), 'class' => 'hidden lg:table-cell'],
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="crm_term('customers')"
        :subtitle="__('Manage your customer accounts')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('customers'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('create', App\Models\Customer::class)
                @if (auth()->user()?->hasPermission('imports.create'))
                    <x-ui.button :href="route('customers.import.create')" variant="secondary" size="sm">{{ __('Import') }}</x-ui.button>
                @endif
                <x-ui.button :href="route('customers.create')" variant="primary" size="sm">{{ __('Add Customer') }}</x-ui.button>
            @endcan
        </x-slot:actions>

        <x-slot:filters>
            <form method="GET" action="{{ route('customers.index') }}" id="customers-index-filters" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-7">
                <div class="lg:col-span-2">
                    <label for="customers-search" class="sr-only">{{ __('Search customers') }}</label>
                    <x-forms.input id="customers-search" name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search name, company, email…') }}" />
                </div>
                <x-forms.select name="status" aria-label="{{ __('Status') }}">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (config('customers.statuses') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.input name="industry" :value="$filters['industry'] ?? ''" placeholder="{{ __('Industry') }}" aria-label="{{ __('Industry') }}" />
                <x-forms.select name="state" aria-label="{{ __('State') }}">
                    <option value="">{{ __('All states') }}</option>
                    @foreach ($stateOptions as $state)
                        <option value="{{ $state }}" @selected(in_array($state, \Illuminate\Support\Arr::wrap($filters['state'] ?? []), true))>{{ $state }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.select name="country" aria-label="{{ __('Country') }}">
                    <option value="">{{ __('All countries') }}</option>
                    @foreach ($countryOptions as $country)
                        <option value="{{ $country }}" @selected(in_array($country, \Illuminate\Support\Arr::wrap($filters['country'] ?? []), true))>{{ $country }}</option>
                    @endforeach
                </x-forms.select>
                <div class="flex gap-2">
                    <x-forms.select name="assigned_to" class="flex-1" aria-label="{{ __('Assignee') }}">
                        <option value="">{{ __('Anyone') }}</option>
                        @foreach ($assignees as $member)
                            <option value="{{ $member->id }}" @selected(($filters['assigned_to'] ?? '') == $member->id)>{{ $member->name }}</option>
                        @endforeach
                    </x-forms.select>
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
                @include('metadata-fields._index_query_controls')
            </form>
            <div class="mt-3">
                @include('metadata-fields._saved_filter_controls', ['filterFormId' => 'customers-index-filters'])
            </div>
        </x-slot:filters>

        @if ($customers->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset
                    variant="generic"
                    :title="__('No customers yet')"
                    :description="__('Add your first customer to get started.')"
                    :action-href="auth()->user()->can('create', App\Models\Customer::class) ? route('customers.create') : null"
                    :action-label="__('Add Customer')"
                />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($customers as $customer)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('customers.show', $customer) }}" class="group block">
                                <p class="text-sm font-semibold text-ink-heading group-hover:text-primary-700">{{ $customer->display_name }}</p>
                                <p class="mt-0.5 text-xs text-ink-muted">{{ $customer->name }}@if($customer->email) · {{ $customer->email }}@endif</p>
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$statusVariant[$customer->status] ?? 'neutral'">{{ $customer->status_label }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell text-sm text-ink-muted">{{ $customer->industry ?? '—' }}</td>
                        <td class="px-4 py-3 hidden lg:table-cell text-sm text-ink-muted">{{ $customer->assignee?->name ?? '—' }}</td>
                        <td class="px-4 py-3 hidden lg:table-cell text-sm text-ink-muted">{{ $customer->city ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($customers->hasPages())
            <x-slot:pagination>
                {{ $customers->links() }}
            </x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
