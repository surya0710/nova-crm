<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Labels')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Labels'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <form method="GET" action="{{ route('project-labels.index') }}" class="mb-6 flex flex-col sm:flex-row gap-3">
        <x-text-input type="search" name="search" :value="request('search')" placeholder="{{ __('Search labels…') }}" class="w-full sm:max-w-xs" />
        <x-secondary-button type="submit">{{ __('Search') }}</x-secondary-button>
        @if (request()->filled('search'))
            <a href="{{ route('project-labels.index') }}" class="inline-flex items-center text-sm text-slate-500 hover:text-slate-800">{{ __('Clear') }}</a>
        @endif
    </form>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($labels->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No labels defined yet.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Label') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Color') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Description') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($labels as $label)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-2 text-sm font-medium text-slate-900">
                                        <span class="inline-flex text-xs font-medium px-2.5 py-0.5 rounded-full border border-slate-200" style="background-color: {{ ($label->color ?? '#64748b') }}20; color: {{ $label->color ?? '#64748b' }}">
                                            {{ $label->name }}
                                        </span>
                                        @if ($label->is_system)
                                            <span class="text-xs text-slate-400">{{ __('System') }}</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-2 text-sm text-slate-600">
                                        <span class="h-3 w-3 rounded-full border border-slate-200" style="background-color: {{ $label->color ?? '#64748b' }}"></span>
                                        {{ $label->color ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 max-w-md truncate">{{ $label->description ?: '—' }}</td>
                                <td class="px-6 py-4 text-right whitespace-nowrap space-x-3">
                                    @can('update', $label)
                                        <a href="{{ route('project-labels.edit', $label) }}" class="text-sm text-slate-500 hover:text-primary-600">{{ __('Edit') }}</a>
                                    @endcan
                                    @can('delete', $label)
                                        <form method="POST" action="{{ route('project-labels.destroy', $label) }}" class="inline" onsubmit="return confirm('{{ __('Delete this label?') }}')">
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
