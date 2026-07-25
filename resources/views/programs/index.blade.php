<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Programs')" :subtitle="__('Coordinate related projects')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Programs'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('create', App\Models\Program::class)
                <x-ui.button :href="route('programs.create')" variant="primary" size="sm">{{ __('Add Program') }}</x-ui.button>
            @endcan
        </x-slot:actions>

    <form method="GET" action="{{ route('programs.index') }}" class="mb-6 rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="lg:col-span-2">
                <x-text-input type="search" name="search" :value="request('search')" placeholder="{{ __('Search programs…') }}" class="w-full" />
            </div>
            <select name="status" class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (config('projects.program_statuses') as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex items-center gap-3">
                <x-text-input type="number" name="portfolio_id" :value="request('portfolio_id')" placeholder="{{ __('Portfolio ID') }}" class="w-full" />
                <x-secondary-button type="submit">{{ __('Filter') }}</x-secondary-button>
            </div>
        </div>
    </form>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($programs->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset
                    variant="programs"
                    :action-href="auth()->user()->can('create', App\Models\Program::class) ? route('programs.create') : null"
                />
            </x-ui.card>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Program') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Portfolio') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Manager') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Projects') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($programs as $program)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="h-3 w-3 rounded-full shrink-0" style="background-color: {{ $program->color ?? '#4f46e5' }}"></span>
                                        <div>
                                            <a href="{{ route('programs.show', $program) }}" class="text-sm font-medium text-slate-900 hover:text-indigo-700">{{ $program->name }}</a>
                                            @if ($program->code)
                                                <p class="text-xs text-slate-500">{{ $program->code }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    @if ($program->portfolio)
                                        <a href="{{ route('portfolios.show', $program->portfolio) }}" class="hover:text-indigo-700">{{ $program->portfolio->name }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $program->manager?->name ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">
                                        {{ config('projects.program_statuses')[$program->status] ?? $program->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $program->projects_count ?? $program->projects->count() }}</td>
                                <td class="px-6 py-4 text-right whitespace-nowrap space-x-3">
                                    @can('viewDashboard', $program)
                                        <a href="{{ route('programs.dashboard', $program) }}" class="text-sm text-slate-500 hover:text-primary-600">{{ __('Dashboard') }}</a>
                                    @endcan
                                    @can('update', $program)
                                        <a href="{{ route('programs.edit', $program) }}" class="text-sm text-slate-500 hover:text-primary-600">{{ __('Edit') }}</a>
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
