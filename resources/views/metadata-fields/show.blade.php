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
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-lg font-semibold text-slate-900">{{ $field->label }}</h1>
                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full {{ $statusColors[$field->status] ?? 'bg-slate-100 text-slate-600' }}">
                        {{ config('metadata.statuses.'.$field->status, ucfirst($field->status)) }}
                    </span>
                </div>
                <p class="text-sm text-slate-500 font-mono">{{ $field->entity_type }}.{{ $field->key }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                @can('update', $field)
                    <a href="{{ route('metadata-fields.edit', $field) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                        {{ __('Edit') }}
                    </a>
                    @if ($field->status === 'draft')
                        <form method="POST" action="{{ route('metadata-fields.publish', $field) }}">
                            @csrf
                            <x-primary-button>{{ __('Publish') }}</x-primary-button>
                        </form>
                    @elseif (in_array($field->status, ['published', 'inactive'], true))
                        <form method="POST" action="{{ route('metadata-fields.activate', $field) }}">
                            @csrf
                            <x-primary-button>{{ __('Activate') }}</x-primary-button>
                        </form>
                    @elseif ($field->status === 'active')
                        <form method="POST" action="{{ route('metadata-fields.deactivate', $field) }}">
                            @csrf
                            <x-secondary-button>{{ __('Deactivate') }}</x-secondary-button>
                        </form>
                    @endif
                @endcan
                @can('delete', $field)
                    @if ($field->status !== 'archived')
                        <form method="POST" action="{{ route('metadata-fields.destroy', $field) }}" onsubmit="return confirm('{{ __('Archive this field? Existing values will be preserved in later runtime phases.') }}')">
                            @csrf
                            @method('DELETE')
                            <x-danger-button>{{ __('Archive') }}</x-danger-button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Definition') }}</h3>
                </div>
                <dl class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Entity') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ config('metadata.entities.'.$field->entity_type, $field->entity_type) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Type') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ config('metadata.field_types.'.$field->type, $field->type) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Stable Key') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 font-mono">{{ $field->key }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Group') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $field->group?->label ?? '—' }}</dd>
                    </div>
                    @if ($field->description)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Description') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 whitespace-pre-line">{{ $field->description }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            @if ($field->options->isNotEmpty())
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="font-semibold text-slate-900">{{ __('Options') }}</h3>
                    </div>
                    <div class="p-6 flex flex-wrap gap-2">
                        @foreach ($field->options as $option)
                            <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-700">
                                <span>{{ $option->label }}</span>
                                <span class="font-mono text-xs text-slate-500">{{ $option->value }}</span>
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Version History') }}</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach ($field->versions as $version)
                        <div class="px-6 py-4 flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-slate-900">{{ __('Version :version', ['version' => $version->version]) }} · {{ ucfirst(str_replace('_', ' ', $version->event)) }}</p>
                                <p class="text-xs text-slate-500">{{ $version->creator?->name ?? __('System') }}</p>
                            </div>
                            <p class="text-xs text-slate-500">{{ $version->created_at->format('M j, Y g:i A') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Capabilities') }}</h3>
                </div>
                <dl class="p-6 space-y-3">
                    @foreach ([
                        'is_required' => 'Required',
                        'is_unique' => 'Unique',
                        'is_searchable' => 'Searchable',
                        'is_filterable' => 'Filterable',
                        'is_sortable' => 'Sortable',
                        'is_reportable' => 'Reportable',
                        'is_exportable' => 'Exportable',
                        'is_api_visible' => 'API Visible',
                        'is_sensitive' => 'Sensitive / PII',
                    ] as $flag => $label)
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-sm text-slate-600">{{ __($label) }}</dt>
                            <dd class="text-sm font-medium {{ $field->{$flag} ? 'text-emerald-700' : 'text-slate-400' }}">{{ $field->{$flag} ? __('Yes') : __('No') }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Provenance') }}</h3>
                </div>
                <dl class="p-6 space-y-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Source') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ config('metadata.sources.'.$field->source, ucfirst($field->source)) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Created') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $field->created_at->format('M j, Y') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
