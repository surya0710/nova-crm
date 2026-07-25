<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Lifecycle stages')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Lifecycle stages'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($stages->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No lifecycle stages defined yet.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Sequence') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Name') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Color') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Default') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($stages as $stage)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $stage->sequence ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-slate-900">{{ $stage->name }}</p>
                                    @if ($stage->description)
                                        <p class="text-xs text-slate-500 mt-0.5">{{ Str::limit($stage->description, 60) }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if ($stage->color)
                                        <span class="inline-flex items-center gap-2 text-sm text-slate-600">
                                            <span class="h-3 w-3 rounded-full" style="background-color: {{ $stage->color }}"></span>
                                            {{ $stage->color }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $stage->is_default ? __('Yes') : __('No') }}</td>
                                <td class="px-6 py-4 text-right">
                                    @can('update', $stage)
                                        <a href="{{ route('project-lifecycle-stages.edit', $stage) }}" class="text-sm text-slate-500 hover:text-primary-600">{{ __('Edit') }}</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($stages->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">{{ $stages->links() }}</div>
            @endif
        @endif
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
