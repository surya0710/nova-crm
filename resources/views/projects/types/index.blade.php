<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Types')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Types'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($types->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No project types defined yet.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Name') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Duration') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Sort') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($types as $type)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-slate-900">{{ $type->name }}</p>
                                    @if ($type->description)
                                        <p class="text-xs text-slate-500 mt-0.5">{{ Str::limit($type->description, 60) }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    {{ $type->default_duration ? $type->default_duration . ' ' . __('days') : '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $type->sort_order ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $type->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $type->is_active ? __('Active') : __('Inactive') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @can('update', $type)
                                        <a href="{{ route('project-types.edit', $type) }}" class="text-sm text-slate-500 hover:text-primary-600">{{ __('Edit') }}</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($types->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">{{ $types->links() }}</div>
            @endif
        @endif
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
