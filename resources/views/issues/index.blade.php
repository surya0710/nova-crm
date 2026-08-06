@php
    $priorityColors = [
        'low' => 'bg-slate-100 text-slate-600',
        'medium' => 'bg-amber-100 text-amber-800',
        'high' => 'bg-red-100 text-red-800',
        'critical' => 'bg-red-200 text-red-900',
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Issues')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Issues'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <form method="GET" action="{{ route('issues.index') }}" class="mb-6 rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <x-text-input type="number" name="project_id" :value="$filters['project_id'] ?? ''" placeholder="{{ __('Project ID') }}" class="w-full" />
            <x-text-input type="number" name="portfolio_id" :value="$filters['portfolio_id'] ?? ''" placeholder="{{ __('Portfolio ID') }}" class="w-full" />
            <x-text-input type="number" name="program_id" :value="$filters['program_id'] ?? ''" placeholder="{{ __('Program ID') }}" class="w-full" />
            <select name="status" class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (config('projects.issue_statuses') as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="priority" class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All priorities') }}</option>
                @foreach (config('projects.issue_priorities') as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['priority'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-secondary-button type="submit">{{ __('Filter') }}</x-secondary-button>
        </div>
    </form>

    @can('create', App\Models\ProjectIssue::class)
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5 mb-6">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Add Issue') }}</h3>
            <form method="POST" action="{{ route('issues.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="sm:col-span-2 lg:col-span-4">
                        <x-input-label for="title" :value="__('Title')" />
                        <x-text-input id="title" name="title" class="block mt-1 w-full" :value="old('title')" required />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="priority" :value="__('Priority')" />
                        <select id="priority" name="priority" class="block mt-1 w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                            @foreach (config('projects.issue_priorities') as $value => $label)
                                <option value="{{ $value }}" @selected(old('priority', 'medium') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="severity" :value="__('Severity')" />
                        <select id="severity" name="severity" class="block mt-1 w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                            @foreach (config('projects.issue_severities') as $value => $label)
                                <option value="{{ $value }}" @selected(old('severity', 'medium') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="block mt-1 w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                            @foreach (config('projects.issue_statuses') as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'open') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <x-primary-button>{{ __('Add Issue') }}</x-primary-button>
            </form>
        </div>
    @endcan

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($issues->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="issues" />
            </x-ui.card>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Issue') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Project') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Priority') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($issues as $issue)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $issue->title }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    @if ($issue->project)
                                        <a href="{{ route('projects.show', $issue->project) }}" class="hover:text-indigo-700">{{ $issue->project->name }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $priorityColors[$issue->priority] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ config('projects.issue_priorities')[$issue->priority] ?? $issue->priority }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ config('projects.issue_statuses')[$issue->status] ?? $issue->status }}</td>
                                <td class="px-6 py-4 text-right">
                                    @can('delete', $issue)
                                        <form method="POST" action="{{ route('issues.destroy', $issue) }}" class="inline" onsubmit="return confirm('{{ __('Delete this issue?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
