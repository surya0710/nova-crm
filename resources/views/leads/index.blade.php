@php
    $statusVariant = [
        'new' => 'info',
        'contacted' => 'info',
        'qualified' => 'primary',
        'proposal_sent' => 'primary',
        'negotiation' => 'warning',
        'won' => 'success',
        'lost' => 'neutral',
        'converted' => 'success',
    ];
    $priorityVariant = [
        'low' => 'neutral',
        'medium' => 'warning',
        'high' => 'danger',
    ];
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        [
            'label' => new \Illuminate\Support\HtmlString(
                '<input type="checkbox" class="rounded border-line text-primary-600" @change="togglePage($event.target.checked)" :checked="pageSelected" :indeterminate="isPartialPage()" aria-label="'.e(__('Select page')).'">'
            ),
            'class' => 'w-10',
        ],
        __('Lead'),
        __('Status'),
        ['label' => __('Source'), 'class' => 'hidden md:table-cell'],
        ['label' => __('Priority'), 'class' => 'hidden lg:table-cell'],
        ['label' => __('Assigned'), 'class' => 'hidden lg:table-cell'],
        ['label' => __('Next Follow-up'), 'class' => 'hidden lg:table-cell'],
        ['label' => __('Budget'), 'align' => 'right'],
    ];
    $pageIds = $leads->getCollection()->pluck('id')->all();
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="crm_term('leads')"
        :subtitle="__('Manage and track your sales leads')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('leads'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('create', App\Models\Lead::class)
                @if (auth()->user()?->hasPermission('imports.create'))
                    <x-dropdown align="right" width="w-56">
                        <x-slot name="trigger">
                            <x-ui.button type="button" variant="secondary" size="sm">
                                {{ __('Import') }}
                                <svg class="h-3.5 w-3.5 text-ink-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </x-ui.button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('leads.import.template.xlsx')">{{ __('Download Excel Template') }}</x-dropdown-link>
                            <x-dropdown-link :href="route('leads.import.template.csv')">{{ __('Download CSV Template') }}</x-dropdown-link>
                            <x-dropdown-link :href="route('leads.import.create')">{{ __('Import Leads') }}</x-dropdown-link>
                        </x-slot>
                    </x-dropdown>
                @endif
                <x-ui.button :href="route('leads.create')" variant="primary" size="sm">{{ __('Add Lead') }}</x-ui.button>
            @endcan
        </x-slot:actions>

        <x-slot:filters>
            <form method="GET" action="{{ route('leads.index') }}" id="leads-index-filters" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-8">
                <div class="lg:col-span-2">
                    <label for="leads-search" class="sr-only">{{ __('Search leads') }}</label>
                    <x-forms.input id="leads-search" name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search by customer, mobile, email or company...') }}" />
                </div>
                <x-forms.select name="status" aria-label="{{ __('Status') }}">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (config('leads.statuses') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.select name="source" aria-label="{{ __('Source') }}">
                    <option value="">{{ __('All sources') }}</option>
                    @foreach (config('leads.sources') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['source'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.select name="priority" aria-label="{{ __('Priority') }}">
                    <option value="">{{ __('All priorities') }}</option>
                    @foreach (config('leads.priorities') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['priority'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
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
                    @if ($canFilterByOwner ?? false)
                        <x-forms.select name="assigned_to" class="flex-1" aria-label="{{ __('Assignee') }}">
                            <option value="">{{ __('Anyone') }}</option>
                            @foreach ($assignees as $member)
                                <option value="{{ $member->id }}" @selected(($filters['assigned_to'] ?? '') == $member->id)>{{ $member->name }}</option>
                            @endforeach
                        </x-forms.select>
                    @endif
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
                @include('metadata-fields._index_query_controls')
            </form>
            <div class="mt-3">
                @include('metadata-fields._saved_filter_controls', ['filterFormId' => 'leads-index-filters'])
            </div>
        </x-slot:filters>

        @if ($leads->isEmpty())
            <x-ui.card>
                @if (! empty($filters['search']))
                    <x-ui.empty-state-preset variant="search" />
                @else
                    <x-ui.empty-state-preset
                        variant="leads"
                        :action-href="auth()->user()->can('create', App\Models\Lead::class) ? route('leads.create') : null"
                    />
                @endif
            </x-ui.card>
        @else
            <x-bulk.toolbar
                entity-type="lead"
                :actions="$bulkActions ?? []"
                :page-ids="$pageIds"
                :redirect-to="route('leads.index')"
                :export-enabled="true"
                :filters="$filters ?? []"
            >
                <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                    @foreach ($leads as $lead)
                        <tr class="hover:bg-surface-muted/60 transition">
                            <td class="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        class="rounded border-line text-primary-600"
                                        @change="toggleId({{ $lead->id }}, $event.target.checked)"
                                        :checked="selected.includes({{ $lead->id }})"
                                    >
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('leads.show', $lead) }}" class="group block">
                                    <p class="text-sm font-semibold text-ink-heading group-hover:text-primary-700">{{ $lead->name }}</p>
                                    @if ($lead->company)
                                        <p class="mt-0.5 text-xs text-ink-muted">{{ $lead->company }}</p>
                                    @endif
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="$statusVariant[$lead->status] ?? 'neutral'">{{ $lead->status_label }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell text-sm text-ink-muted">{{ $lead->source_label }}</td>
                            <td class="px-4 py-3 hidden lg:table-cell">
                                <x-ui.badge :variant="$priorityVariant[$lead->priority] ?? 'neutral'">{{ $lead->priority_label }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell text-sm text-ink-muted">{{ $lead->assignee?->name ?? '—' }}</td>
                            <td class="px-4 py-3 hidden lg:table-cell text-sm">
                                @if ($lead->next_follow_up_at)
                                    <span @class(['font-medium text-warning' => $lead->isFollowUpDue(), 'text-ink-muted' => ! $lead->isFollowUpDue()])>
                                        {{ $lead->next_follow_up_at->timezone(app(\App\Services\LeadFollowUpService::class)->organizationTimezone())->format('M j, g:i A') }}
                                    </span>
                                @else
                                    <span class="text-ink-muted">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-ink-heading">
                                {{ $lead->budget ? number_format($lead->budget, 0) : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </x-tables.table>
            </x-bulk.toolbar>
        @endif

        @if ($leads->hasPages())
            <x-slot:pagination>
                {{ $leads->links() }}
            </x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
