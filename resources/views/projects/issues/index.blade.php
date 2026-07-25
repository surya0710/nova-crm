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


    @can('createIssues', $project)
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5 mb-6">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Add Issue') }}</h3>
            <form method="POST" action="{{ route('projects.issues.store', $project) }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <x-input-label for="title" :value="__('Title')" />
                        <x-text-input id="title" name="title" class="block mt-1 w-full" :value="old('title')" required />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea id="description" name="description" rows="2" class="block mt-1 w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm">{{ old('description') }}</textarea>
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
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No issues recorded for this project.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Issue') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Priority') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Severity') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Due') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($issues as $issue)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-slate-900">{{ $issue->title }}</p>
                                    @if ($issue->description)
                                        <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ $issue->description }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $priorityColors[$issue->priority] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ config('projects.issue_priorities')[$issue->priority] ?? $issue->priority }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ config('projects.issue_severities')[$issue->severity] ?? $issue->severity ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ config('projects.issue_statuses')[$issue->status] ?? $issue->status }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $issue->due_date?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-6 py-4 text-right">
                                    @can('delete', $issue)
                                        <form method="POST" action="{{ route('projects.issues.destroy', [$project, $issue]) }}" class="inline" onsubmit="return confirm('{{ __('Delete this issue?') }}')">
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
