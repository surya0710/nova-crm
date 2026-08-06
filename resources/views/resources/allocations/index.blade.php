<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Allocations')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Allocations'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-6">
        <form method="GET" action="{{ route('resources.allocations.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <x-text-input name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search…') }}" class="w-full" />
            <select name="employee_id" class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All employees') }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(($filters['employee_id'] ?? '') == $employee->id)>{{ $employee->full_name }}</option>
                @endforeach
            </select>
            <select name="project_id" class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All projects') }}</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected(($filters['project_id'] ?? '') == $project->id)>{{ $project->name }}</option>
                @endforeach
            </select>
            <select name="allocation_type" class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All types') }}</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['allocation_type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($allocations->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No allocations found.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Employee') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Type') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Project') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('%') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Dates') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($allocations as $allocation)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('resources.allocations.show', $allocation) }}" class="font-medium text-primary-600 hover:text-primary-700">
                                        {{ $allocation->employee?->full_name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $allocation->allocation_type_label }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $allocation->project?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $allocation->allocation_percentage }}%</td>
                                <td class="px-4 py-3 text-slate-500">{{ $allocation->planned_start_date->toDateString() }} → {{ $allocation->planned_end_date->toDateString() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-200">{{ $allocations->links() }}</div>
        @endif
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
