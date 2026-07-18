@php
    $statusColors = [
        'new' => 'bg-blue-100 text-blue-800',
        'contacted' => 'bg-cyan-100 text-cyan-800',
        'qualified' => 'bg-indigo-100 text-indigo-800',
        'proposal_sent' => 'bg-violet-100 text-violet-800',
        'negotiation' => 'bg-amber-100 text-amber-800',
        'won' => 'bg-emerald-100 text-emerald-800',
        'lost' => 'bg-slate-100 text-slate-600',
    ];
    $priorityColors = [
        'low' => 'bg-slate-100 text-slate-600',
        'medium' => 'bg-amber-100 text-amber-800',
        'high' => 'bg-red-100 text-red-800',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ crm_term('leads') }}</h1>
                <p class="text-sm text-slate-500">{{ __('Manage and track your sales leads') }}</p>
            </div>
            @can('create', App\Models\Lead::class)
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    @if (auth()->user()?->hasPermission('imports.create'))
                        <x-dropdown align="right" width="w-56">
                            <x-slot name="trigger">
                                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    {{ __('Import') }}
                                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('leads.import.template.xlsx')">
                                    {{ __('Download Excel Template') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('leads.import.template.csv')">
                                    {{ __('Download CSV Template') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('leads.import.create')">
                                    {{ __('Import Leads') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    @endif
                    <a href="{{ route('leads.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ __('Add Lead') }}
                    </a>
                </div>
            @endcan
        </div>
    </x-slot>

    <x-flash-messages />

    {{-- Filters --}}
    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-6 space-y-3">
        <form method="GET" action="{{ route('leads.index') }}" id="leads-index-filters" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div class="lg:col-span-2">
                <x-text-input name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search name, company, email…') }}" class="w-full" />
            </div>
            <select name="status" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (config('leads.statuses') as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="source" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All sources') }}</option>
                @foreach (config('leads.sources') as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['source'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="priority" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All priorities') }}</option>
                @foreach (config('leads.priorities') as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['priority'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <select name="assigned_to" class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('Anyone') }}</option>
                    @foreach ($assignees as $member)
                        <option value="{{ $member->id }}" @selected(($filters['assigned_to'] ?? '') == $member->id)>{{ $member->name }}</option>
                    @endforeach
                </select>
                <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
            </div>
            @include('metadata-fields._index_query_controls')
        </form>
        @include('metadata-fields._saved_filter_controls', ['filterFormId' => 'leads-index-filters'])
    </div>

    {{-- Table --}}
    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($leads->isEmpty())
            <div class="p-12 text-center">
                <div class="mx-auto h-12 w-12 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mb-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">{{ __('No leads yet') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('Get started by adding your first lead.') }}</p>
                @can('create', App\Models\Lead::class)
                    <a href="{{ route('leads.create') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                        {{ __('Add Lead') }} →
                    </a>
                @endcan
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Lead') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 hidden md:table-cell">{{ __('Source') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 hidden lg:table-cell">{{ __('Priority') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 hidden lg:table-cell">{{ __('Assigned') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 hidden lg:table-cell">{{ __('Next Follow-up') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Budget') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($leads as $lead)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-6 py-4">
                                    <a href="{{ route('leads.show', $lead) }}" class="group">
                                        <p class="text-sm font-semibold text-slate-900 group-hover:text-indigo-600">{{ $lead->name }}</p>
                                        @if ($lead->company)
                                            <p class="text-xs text-slate-500 mt-0.5">{{ $lead->company }}</p>
                                        @endif
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $statusColors[$lead->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $lead->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 hidden md:table-cell text-sm text-slate-600">{{ $lead->source_label }}</td>
                                <td class="px-6 py-4 hidden lg:table-cell">
                                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $priorityColors[$lead->priority] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $lead->priority_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 hidden lg:table-cell text-sm text-slate-600">{{ $lead->assignee?->name ?? '—' }}</td>
                                <td class="px-6 py-4 hidden lg:table-cell text-sm">
                                    @if ($lead->next_follow_up_at)
                                        <span class="{{ $lead->isFollowUpDue() ? 'text-amber-600 font-medium' : 'text-slate-600' }}">
                                            {{ $lead->next_follow_up_at->timezone(app(\App\Services\LeadFollowUpService::class)->organizationTimezone())->format('M j, g:i A') }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-slate-900">
                                    @if ($lead->budget)
                                        {{ number_format($lead->budget, 0) }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($leads->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">
                    {{ $leads->links() }}
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
