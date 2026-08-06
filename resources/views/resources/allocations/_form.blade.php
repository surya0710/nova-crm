<div class="space-y-5">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="employee_id" :value="__('Employee')" />
            <select id="employee_id" name="employee_id" required class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('Select employee') }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected((int) old('employee_id', $allocation->employee_id) === $employee->id)>{{ $employee->full_name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('employee_id')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="allocation_type" :value="__('Allocation type')" />
            <select id="allocation_type" name="allocation_type" required class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected(old('allocation_type', $allocation->allocation_type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('allocation_type')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="project_id" :value="__('Project')" />
            <select id="project_id" name="project_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('None') }}</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected((int) old('project_id', $allocation->project_id) === $project->id)>{{ $project->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('project_id')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="task_id" :value="__('Task')" />
            <select id="task_id" name="task_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('None') }}</option>
                @foreach ($tasks as $task)
                    <option value="{{ $task->id }}" @selected((int) old('task_id', $allocation->task_id) === $task->id)>{{ $task->title }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('task_id')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <x-input-label for="allocation_percentage" :value="__('Allocation %')" />
            <x-text-input id="allocation_percentage" name="allocation_percentage" type="number" min="1" max="{{ (int) config('resources.max_allocation_percentage', 100) }}" class="mt-1 block w-full" :value="old('allocation_percentage', $allocation->allocation_percentage)" required />
            <x-input-error :messages="$errors->get('allocation_percentage')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="planned_start_date" :value="__('Start date')" />
            <x-text-input id="planned_start_date" name="planned_start_date" type="date" class="mt-1 block w-full" :value="old('planned_start_date', optional($allocation->planned_start_date)->toDateString())" required />
            <x-input-error :messages="$errors->get('planned_start_date')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="planned_end_date" :value="__('End date')" />
            <x-text-input id="planned_end_date" name="planned_end_date" type="date" class="mt-1 block w-full" :value="old('planned_end_date', optional($allocation->planned_end_date)->toDateString())" required />
            <x-input-error :messages="$errors->get('planned_end_date')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="planned_hours" :value="__('Planned hours')" />
            <x-text-input id="planned_hours" name="planned_hours" type="number" step="0.25" min="0" class="mt-1 block w-full" :value="old('planned_hours', $allocation->planned_hours)" />
            <x-input-error :messages="$errors->get('planned_hours')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="notes" :value="__('Notes')" />
            <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('notes', $allocation->notes) }}</textarea>
            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
        </div>
    </div>

    @if (isset($metadataFields) && isset($metadataPresenter))
        @include('metadata-fields._runtime_form', [
            'metadataFields' => $metadataFields,
            'metadataPresenter' => $metadataPresenter,
            'record' => $allocation,
        ])
    @endif
</div>
