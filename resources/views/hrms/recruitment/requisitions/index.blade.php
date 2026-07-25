<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Job Requisitions')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Job Requisitions'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\JobRequisition::class)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <form method="POST" action="{{ route('hrms.recruitment.requisitions.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @csrf
            <select name="department_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Department') }}</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                @endforeach
            </select>
            <select name="designation_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Designation') }}</option>
                @foreach ($designations as $designation)
                    <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                @endforeach
            </select>
            <select name="employment_type" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                @foreach ($employmentTypes as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <select name="hiring_manager_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">{{ __('Hiring Manager') }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                @endforeach
            </select>
            <x-forms.input name="number_of_positions" type="number" min="1" value="1" placeholder="{{ __('Positions') }}" required  />
            <x-forms.input name="target_joining_date" type="date" placeholder="{{ __('Target Joining Date') }}"  />
            <x-forms.input name="budget" type="number" step="0.01" placeholder="{{ __('Budget') }}"  />
            <textarea name="business_justification" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="2" placeholder="{{ __('Business Justification') }}" required></textarea>
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Create Requisition') }}</x-ui.button>
        </form>
    </div>
    @endcan
    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Department') }}</th>
                    <th class="p-3 text-left">{{ __('Designation') }}</th>
                    <th class="p-3 text-left">{{ __('Positions') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($requisitions as $requisition)
                <tr class="border-t">
                    <td class="p-3">{{ $requisition->department?->name }}</td>
                    <td class="p-3">{{ $requisition->designation?->name }}</td>
                    <td class="p-3">{{ $requisition->number_of_positions }}</td>
                    <td class="p-3">{{ $requisition->statusLabel() }}</td>
                    <td class="p-3"><a href="{{ route('hrms.recruitment.requisitions.show', $requisition) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ __('View') }}</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $requisitions->links() }}</div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
