<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Goals')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Goals'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\Goal::class)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <h2 class="font-medium text-slate-800 mb-3">{{ __('Assign Goal') }}</h2>
        <form method="POST" action="{{ route('hrms.performance.goals.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @csrf
            <select name="performance_cycle_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Performance Cycle') }}</option>
                @foreach ($cycles as $cycle)
                    <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                @endforeach
            </select>
            <select name="goal_template_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">{{ __('Template (optional)') }}</option>
                @foreach ($templates as $template)
                    <option value="{{ $template->id }}">{{ $template->title }}</option>
                @endforeach
            </select>
            <select name="kpi_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">{{ __('KPI (optional)') }}</option>
                @foreach ($kpis as $kpi)
                    <option value="{{ $kpi->id }}">{{ $kpi->name }}</option>
                @endforeach
            </select>
            <x-forms.input name="title" placeholder="{{ __('Title (if no template)') }}"  />
            <select name="assignee_type" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                @foreach ($assigneeTypes as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <select name="employee_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">{{ __('Employee') }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                @endforeach
            </select>
            <select name="team_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">{{ __('Team') }}</option>
                @foreach ($teams as $team)
                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                @endforeach
            </select>
            <select name="department_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">{{ __('Department') }}</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                @endforeach
            </select>
            <x-forms.input name="target_value" type="number" step="0.01" placeholder="{{ __('Target') }}"  />
            <x-forms.input name="weight" type="number" step="0.01" placeholder="{{ __('Weight %') }}" value="100"  />
            <x-forms.input name="due_date" type="date"  />
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Assign Goal') }}</x-ui.button>
        </form>
    </div>
    @endcan
    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Title') }}</th>
                    <th class="p-3 text-left">{{ __('Assignee') }}</th>
                    <th class="p-3 text-left">{{ __('Cycle') }}</th>
                    <th class="p-3 text-left">{{ __('Weight') }}</th>
                    <th class="p-3 text-left">{{ __('Achievement') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($goals as $goal)
                <tr class="border-t">
                    <td class="p-3"><a class="text-sm font-medium text-primary-700 hover:text-primary-800" href="{{ route('hrms.performance.goals.show', $goal) }}">{{ $goal->title }}</a></td>
                    <td class="p-3">
                        @if ($goal->assignee_type === 'employee')
                            {{ $goal->employee?->first_name }} {{ $goal->employee?->last_name }}
                        @elseif ($goal->assignee_type === 'team')
                            {{ $goal->team?->name }}
                        @elseif ($goal->assignee_type === 'department')
                            {{ $goal->department?->name }}
                        @else
                            {{ __('Organization') }}
                        @endif
                    </td>
                    <td class="p-3">{{ $goal->cycle?->name }}</td>
                    <td class="p-3">{{ $goal->weight }}%</td>
                    <td class="p-3">{{ $goal->achievement_percentage }}%</td>
                    <td class="p-3">{{ $statuses[$goal->status] ?? $goal->status }}</td>
                    <td class="p-3">
                        <a class="text-sm font-medium text-primary-700 hover:text-primary-800" href="{{ route('hrms.performance.goals.show', $goal) }}">{{ __('View') }}</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $goals->links() }}</div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
