@php
    $stageColors = [
        'qualification' => 'bg-blue-100 text-blue-800',
        'proposal' => 'bg-violet-100 text-violet-800',
        'negotiation' => 'bg-amber-100 text-amber-800',
        'closed_won' => 'bg-emerald-100 text-emerald-800',
        'closed_lost' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ crm_term('pipeline') }}</h1>
                <p class="text-sm text-slate-500">{{ __('Track deals through your sales pipeline') }}</p>
            </div>
            @can('create', App\Models\Opportunity::class)
                <a href="{{ route('pipeline.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition shrink-0">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('Add Deal') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        @php $currency = $organization->currency ?? 'USD'; @endphp
        <div class="rounded-xl bg-white border border-slate-200 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Open Deals') }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $pipelineSummary['open_count'] }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ __('Active in pipeline') }}</p>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Open Pipeline Value') }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($pipelineSummary['open_value'], 0) }} {{ $currency }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ __('Excludes closed deals') }}</p>
        </div>
        <a href="{{ route('pipeline.index', ['stage' => 'closed_won']) }}" class="rounded-xl bg-white border border-slate-200 p-4 hover:border-emerald-200 transition block">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Won') }}</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700">{{ $pipelineSummary['won_count'] }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ __('Closed won') }}</p>
        </a>
        <a href="{{ route('pipeline.index', ['stage' => 'closed_lost']) }}" class="rounded-xl bg-white border border-slate-200 p-4 hover:border-slate-300 transition block">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Lost') }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-600">{{ $pipelineSummary['lost_count'] }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ __('Closed lost') }}</p>
        </a>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
        @foreach (config('pipeline.stages') as $value => $label)
            <a href="{{ route('pipeline.index', ['stage' => $value]) }}" class="rounded-xl bg-white border {{ ($filters['stage'] ?? '') === $value ? 'border-indigo-300 ring-1 ring-indigo-200' : 'border-slate-200' }} p-4 hover:border-indigo-200 transition">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 truncate">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ $stageCounts[$value] ?? 0 }}</p>
            </a>
        @endforeach
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-6 space-y-3">
        <form method="GET" action="{{ route('pipeline.index') }}" id="pipeline-index-filters" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="lg:col-span-2">
                <x-text-input name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search deals or customers…') }}" class="w-full" />
            </div>
            <select name="stage" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All stages') }}</option>
                @foreach (config('pipeline.stages') as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['stage'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="customer_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All customers') }}</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected(($filters['customer_id'] ?? '') == $customer->id)>{{ $customer->display_name }}</option>
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
        @include('metadata-fields._saved_filter_controls', ['filterFormId' => 'pipeline-index-filters'])
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($opportunities->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No deals in the pipeline yet.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Deal') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Stage') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Value') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 hidden md:table-cell">{{ __('Customer') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 hidden lg:table-cell">{{ __('Close Date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($opportunities as $opportunity)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-6 py-4">
                                    <a href="{{ route('pipeline.show', $opportunity) }}" class="text-sm font-semibold text-slate-900 hover:text-indigo-600">{{ $opportunity->title }}</a>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $opportunity->assignee?->name ?? __('Unassigned') }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $stageColors[$opportunity->stage] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $opportunity->stage_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium text-slate-900">
                                    @if ($opportunity->amount)
                                        {{ $opportunity->currency }} {{ number_format($opportunity->amount, 0) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4 hidden md:table-cell text-sm text-slate-600">{{ $opportunity->customer?->display_name ?? '—' }}</td>
                                <td class="px-6 py-4 hidden lg:table-cell text-sm text-slate-600">{{ $opportunity->expected_close_date?->format('M j, Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($opportunities->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">{{ $opportunities->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
