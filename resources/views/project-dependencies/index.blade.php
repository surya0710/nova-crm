@php
    $nodes = collect($graph['nodes'] ?? [])->keyBy('id');
    $dependencyTypes = config('projects.dependency_types', []);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ __('Project Dependencies') }}</h1>
                <p class="text-sm text-slate-500">{{ __('Cross-project dependency graph and register') }}</p>
            </div>
        </div>
    </x-slot>

    <x-flash-messages />

    <form method="GET" action="{{ route('project-dependencies.index') }}" class="mb-6 rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5">
        <div class="flex flex-col sm:flex-row gap-3">
            <x-text-input type="number" name="portfolio_id" :value="request('portfolio_id')" placeholder="{{ __('Portfolio ID (optional)') }}" class="w-full sm:max-w-xs" />
            <x-secondary-button type="submit">{{ __('Filter Graph') }}</x-secondary-button>
            @if ($portfolio)
                <span class="inline-flex items-center text-sm text-slate-600">{{ __('Filtered:') }} <strong class="ml-1">{{ $portfolio->name }}</strong></span>
            @endif
        </div>
    </form>

    @can('create', App\Models\ProjectDependency::class)
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5 mb-6">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Add Dependency') }}</h3>
            <form method="POST" action="{{ route('project-dependencies.store') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                @csrf
                <div>
                    <x-input-label for="predecessor_project_id" :value="__('Predecessor Project ID')" />
                    <x-text-input id="predecessor_project_id" name="predecessor_project_id" type="number" class="block mt-1 w-full" :value="old('predecessor_project_id')" required />
                    <x-input-error :messages="$errors->get('predecessor_project_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="successor_project_id" :value="__('Successor Project ID')" />
                    <x-text-input id="successor_project_id" name="successor_project_id" type="number" class="block mt-1 w-full" :value="old('successor_project_id')" required />
                    <x-input-error :messages="$errors->get('successor_project_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="dependency_type" :value="__('Type')" />
                    <select id="dependency_type" name="dependency_type" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        @foreach ($dependencyTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('dependency_type', 'finish_to_start') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="lag_days" :value="__('Lag Days')" />
                    <x-text-input id="lag_days" name="lag_days" type="number" class="block mt-1 w-full" :value="old('lag_days', 0)" />
                </div>
                <div class="flex items-end">
                    <x-primary-button class="w-full justify-center">{{ __('Add') }}</x-primary-button>
                </div>
            </form>
        </div>
    @endcan

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6 mb-6">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Dependency Graph') }}</h3>
        @if (empty($graph['nodes']))
            <p class="text-sm text-slate-500">{{ __('No projects available for graph visualization.') }}</p>
        @else
            <div class="space-y-3">
                @foreach ($graph['edges'] ?? [] as $edge)
                    @php
                        $from = $nodes->get($edge['from']);
                        $to = $nodes->get($edge['to']);
                    @endphp
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 font-medium text-slate-900">
                            {{ $from['name'] ?? '#'.$edge['from'] }}
                        </span>
                        <span class="text-slate-400">→</span>
                        <span class="inline-flex items-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 font-medium text-indigo-900">
                            {{ $to['name'] ?? '#'.$edge['to'] }}
                        </span>
                        <span class="text-xs text-slate-500">{{ $dependencyTypes[$edge['type'] ?? ''] ?? ($edge['type'] ?? '') }}</span>
                        @if (($edge['lag_days'] ?? 0) != 0)
                            <span class="text-xs text-slate-500">{{ __('Lag: :days days', ['days' => $edge['lag_days']]) }}</span>
                        @endif
                    </div>
                @endforeach
                @if (empty($graph['edges']))
                    <p class="text-sm text-slate-500">{{ __('No dependency edges defined yet.') }}</p>
                @endif
            </div>
            <div class="mt-6 pt-6 border-t border-slate-100">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">{{ __('Nodes') }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($graph['nodes'] as $node)
                        <span class="inline-flex text-xs font-medium px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">
                            {{ $node['name'] ?? '#'.$node['id'] }} ({{ $node['completion_percentage'] ?? 0 }}%)
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-semibold text-slate-900">{{ __('Dependency Register') }}</h3>
        </div>
        @if ($dependencies->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No dependencies recorded.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Predecessor') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Successor') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Type') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Lag') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($dependencies as $dependency)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4 text-sm text-slate-900">{{ $dependency->predecessor?->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-900">{{ $dependency->successor?->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $dependencyTypes[$dependency->dependency_type] ?? $dependency->dependency_type }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $dependency->lag_days ?? 0 }}</td>
                                <td class="px-6 py-4 text-right">
                                    @can('delete', $dependency)
                                        <form method="POST" action="{{ route('project-dependencies.destroy', $dependency) }}" class="inline" onsubmit="return confirm('{{ __('Delete this dependency?') }}')">
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
            @if ($dependencies->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">{{ $dependencies->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
