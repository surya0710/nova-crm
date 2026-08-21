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
        ['label' => __('Lifecycle'), 'class' => 'hidden md:table-cell'],
        ['label' => __('Industry'), 'class' => 'hidden lg:table-cell'],
        ['label' => __('Account Manager'), 'class' => 'hidden xl:table-cell'],
        ['label' => __('Location'), 'class' => 'hidden xl:table-cell'],
        ['label' => __('Actions'), 'class' => 'text-right'],
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
            <form method="GET" action="{{ route('customers.index') }}" id="customers-index-filters" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                <div class="sm:col-span-2">
                    <label for="customers-search" class="sr-only">{{ __('Search customers') }}</label>
                    <x-forms.input id="customers-search" name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search name, company, email, or contact…') }}" />
                </div>
                <x-forms.select name="status" aria-label="{{ __('Status') }}">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (config('customers.statuses') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.select name="type" aria-label="{{ __('Type') }}">
                    <option value="">{{ __('All types') }}</option>
                    @foreach (config('customers.types') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.select name="lifecycle_stage" aria-label="{{ __('Lifecycle') }}">
                    <option value="">{{ __('All lifecycle stages') }}</option>
                    @foreach (config('customers.lifecycle_stages') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['lifecycle_stage'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.select name="segment" aria-label="{{ __('Segment') }}">
                    <option value="">{{ __('All segments') }}</option>
                    @foreach (config('customers.segments') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['segment'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.select name="source" aria-label="{{ __('Source') }}">
                    <option value="">{{ __('All sources') }}</option>
                    @foreach (config('customers.sources') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['source'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.input name="industry" :value="$filters['industry'] ?? ''" placeholder="{{ __('Industry') }}" aria-label="{{ __('Industry') }}" />
                <x-forms.input name="tags" :value="$filters['tags'] ?? ''" placeholder="{{ __('Tags') }}" aria-label="{{ __('Tags') }}" />
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
                <x-forms.input type="date" name="created_from" :value="$filters['created_from'] ?? ''" aria-label="{{ __('Created from') }}" />
                <x-forms.input type="date" name="created_to" :value="$filters['created_to'] ?? ''" aria-label="{{ __('Created to') }}" />
                <x-forms.input type="date" name="last_activity_from" :value="$filters['last_activity_from'] ?? ''" aria-label="{{ __('Last activity from') }}" />
                <x-forms.input type="date" name="last_activity_to" :value="$filters['last_activity_to'] ?? ''" aria-label="{{ __('Last activity to') }}" />
                <x-forms.input type="number" name="value_min" :value="$filters['value_min'] ?? ''" placeholder="{{ __('Min value') }}" min="0" step="0.01" aria-label="{{ __('Customer value from') }}" />
                <x-forms.input type="number" name="value_max" :value="$filters['value_max'] ?? ''" placeholder="{{ __('Max value') }}" min="0" step="0.01" aria-label="{{ __('Customer value to') }}" />
                <div class="flex gap-2">
                    <x-forms.select name="assigned_to" class="flex-1" aria-label="{{ __('Owner') }}">
                        <option value="">{{ __('Anyone') }}</option>
                        @foreach ($assignees as $member)
                            <option value="{{ $member->id }}" @selected(($filters['assigned_to'] ?? '') == $member->id)>{{ $member->name }}</option>
                        @endforeach
                    </x-forms.select>
                </div>
                <div class="flex gap-2">
                    <x-forms.select name="sort" class="flex-1" aria-label="{{ __('Sort') }}">
                        <option value="">{{ __('Newest first') }}</option>
                        <option value="name" @selected(($filters['sort'] ?? '') === 'name')>{{ __('Name') }}</option>
                        <option value="created_at" @selected(($filters['sort'] ?? '') === 'created_at')>{{ __('Created date') }}</option>
                        <option value="last_activity_at" @selected(($filters['sort'] ?? '') === 'last_activity_at')>{{ __('Last activity') }}</option>
                        <option value="customer_value" @selected(($filters['sort'] ?? '') === 'customer_value')>{{ __('Customer value') }}</option>
                    </x-forms.select>
                    <x-forms.select name="sort_direction" class="w-24" aria-label="{{ __('Sort direction') }}">
                        <option value="desc" @selected(($filters['sort_direction'] ?? 'desc') === 'desc')>{{ __('Desc') }}</option>
                        <option value="asc" @selected(($filters['sort_direction'] ?? '') === 'asc')>{{ __('Asc') }}</option>
                    </x-forms.select>
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
                @include('metadata-fields._index_query_controls')
            </form>
            <div class="mt-3 space-y-3">
                @include('metadata-fields._filter_chips', [
                    'chipRoute' => 'customers.index',
                    'clearHref' => route('customers.index', ['view' => 'all']),
                    'chipLabels' => [
                        'search' => __('Search'),
                        'status' => ['_label' => __('Status')] + config('customers.statuses'),
                        'type' => ['_label' => __('Type')] + config('customers.types'),
                        'lifecycle_stage' => ['_label' => __('Lifecycle')] + config('customers.lifecycle_stages'),
                        'segment' => ['_label' => __('Segment')] + config('customers.segments'),
                        'source' => ['_label' => __('Source')] + config('customers.sources'),
                        'industry' => __('Industry'),
                        'tags' => __('Tags'),
                        'created_from' => __('Created from'),
                        'created_to' => __('Created to'),
                        'last_activity_from' => __('Last activity from'),
                        'last_activity_to' => __('Last activity to'),
                        'value_min' => __('Min value'),
                        'value_max' => __('Max value'),
                        'assigned_to' => __('Owner'),
                    ],
                ])
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
                        <td class="px-4 py-3 hidden md:table-cell text-sm text-ink-muted">{{ $customer->lifecycle_stage_label }}</td>
                        <td class="px-4 py-3 hidden lg:table-cell text-sm text-ink-muted">{{ $customer->industry ?? '—' }}</td>
                        <td class="px-4 py-3 hidden xl:table-cell text-sm text-ink-muted">{{ $customer->assignee?->name ?? '—' }}</td>
                        <td class="px-4 py-3 hidden xl:table-cell text-sm text-ink-muted">{{ $customer->city ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('update', $customer)
                                <a href="{{ route('customers.show', $customer) }}#email-composer" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('Email') }}</a>
                            @endcan
                        </td>
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
