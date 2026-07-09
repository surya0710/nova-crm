@php
    $statusColors = [
        'draft' => 'bg-slate-100 text-slate-700',
        'published' => 'bg-blue-100 text-blue-800',
        'active' => 'bg-emerald-100 text-emerald-800',
        'inactive' => 'bg-amber-100 text-amber-800',
        'archived' => 'bg-rose-100 text-rose-800',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ __('Metadata Fields') }}</h1>
                <p class="text-sm text-slate-500">{{ __('Organization-owned field definitions for dynamic forms, validation, APIs, search, reports, automation, and AI.') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                @if (($organization->settings['field_blueprints'] ?? []) !== [] && auth()->user()?->hasPermission('metadata.manage', $organization))
                    <form method="POST" action="{{ route('metadata-fields.activate-blueprints') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                            {{ __('Activate Template Fields') }}
                        </button>
                    </form>
                @endif
                @can('create', App\Models\MetadataFieldDefinition::class)
                    <a href="{{ route('metadata-fields.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ __('New Field') }}
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <x-flash-messages />

    @if (session('metadata_activation_summary'))
        @php($activation = session('metadata_activation_summary'))
        <div class="mb-6 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
            {{ __('Template field activation complete: :activated activated, :skipped skipped.', [
                'activated' => $activation['activated'] ?? 0,
                'skipped' => $activation['skipped'] ?? 0,
            ]) }}
        </div>
    @endif

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-6">
        <form method="GET" action="{{ route('metadata-fields.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="lg:col-span-2">
                <x-text-input name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search label or key…') }}" class="w-full" />
            </div>
            <select name="entity" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All entities') }}</option>
                @foreach (config('metadata.entities') as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['entity'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (config('metadata.statuses') as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-primary-button type="submit" class="justify-center">{{ __('Filter') }}</x-primary-button>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($fields->isEmpty())
            <div class="p-12 text-center">
                <div class="mx-auto h-12 w-12 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mb-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h10M4 18h7M17 10v8m-4-4h8"/></svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">{{ __('No metadata fields yet') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('Create tenant-owned metadata before wiring dynamic runtime forms in later phases.') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Field') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Entity') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Type') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 hidden lg:table-cell">{{ __('Capabilities') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($fields as $field)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-6 py-4">
                                    <a href="{{ route('metadata-fields.show', $field) }}" class="group">
                                        <p class="text-sm font-semibold text-slate-900 group-hover:text-indigo-600">{{ $field->label }}</p>
                                        <p class="text-xs text-slate-500 mt-0.5 font-mono">{{ $field->key }}@if($field->group) · {{ $field->group->label }}@endif</p>
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ config('metadata.entities.'.$field->entity_type, $field->entity_type) }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ config('metadata.field_types.'.$field->type, $field->type) }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $statusColors[$field->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ config('metadata.statuses.'.$field->status, ucfirst($field->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 hidden lg:table-cell">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ([
                                            'is_required' => 'Required',
                                            'is_searchable' => 'Search',
                                            'is_reportable' => 'Reports',
                                            'is_sensitive' => 'Sensitive',
                                        ] as $flag => $label)
                                            @if ($field->{$flag})
                                                <span class="inline-flex text-[11px] font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">{{ __($label) }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($fields->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">{{ $fields->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
