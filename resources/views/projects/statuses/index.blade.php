<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Statuses')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Statuses'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($statuses->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No project statuses defined yet.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Name') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Color') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Default') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Closed') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Sort') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($statuses as $status)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $status->name }}</td>
                                <td class="px-6 py-4">
                                    @if ($status->color)
                                        <span class="inline-flex items-center gap-2 text-sm text-slate-600">
                                            <span class="h-3 w-3 rounded-full" style="background-color: {{ $status->color }}"></span>
                                            {{ $status->color }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $status->is_default ? __('Yes') : __('No') }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $status->is_closed ? __('Yes') : __('No') }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $status->sort_order ?? '—' }}</td>
                                <td class="px-6 py-4 text-right">
                                    @can('update', $status)
                                        <a href="{{ route('project-statuses.edit', $status) }}" class="text-sm text-slate-500 hover:text-primary-600">{{ __('Edit') }}</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($statuses->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">{{ $statuses->links() }}</div>
            @endif
        @endif
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
