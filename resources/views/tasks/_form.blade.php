@php
    $currentTaskableType = null;
    $currentTaskableId = null;

    if ($task->taskable_type && $task->taskable_id) {
        foreach (config('tasks.taskable') as $key => $class) {
            if ($task->taskable_type === $class) {
                $currentTaskableType = $key;
                $currentTaskableId = $task->taskable_id;
                break;
            }
        }
    }
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div class="sm:col-span-2">
        <x-input-label for="title" :value="__('Title')" />
        <x-text-input id="title" name="title" class="block mt-1 w-full" type="text" :value="old('title', $task->title)" required autofocus />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="description" :value="__('Description')" />
        <textarea id="description" name="description" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $task->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="status" :value="__('Status')" />
        <select id="status" name="status" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
            @foreach (config('tasks.statuses') as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $task->status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="priority" :value="__('Priority')" />
        <select id="priority" name="priority" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
            @foreach (config('tasks.priorities') as $value => $label)
                <option value="{{ $value }}" @selected(old('priority', $task->priority) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('priority')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="due_at" :value="__('Due Date')" />
        <x-text-input id="due_at" name="due_at" class="block mt-1 w-full" type="datetime-local" :value="old('due_at', $task->due_at?->format('Y-m-d\TH:i'))" />
        <x-input-error :messages="$errors->get('due_at')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="assigned_to" :value="__('Assigned To')" />
        <select id="assigned_to" name="assigned_to" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
            <option value="">{{ __('Unassigned') }}</option>
            @foreach ($assignees as $member)
                <option value="{{ $member->id }}" @selected(old('assigned_to', $task->assigned_to) == $member->id)>{{ $member->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('assigned_to')" class="mt-2" />
    </div>

    @if (empty($hideTaskable))
        <div>
            <x-input-label for="taskable_type" :value="__('Related Record Type')" />
            <select id="taskable_type" name="taskable_type" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('None') }}</option>
                @foreach (config('tasks.taskable') as $key => $class)
                    <option value="{{ $key }}" @selected(old('taskable_type', $currentTaskableType) === $key)>{{ ucfirst($key === 'opportunity' ? crm_term('deal') : crm_term($key)) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <x-input-label for="taskable_id" :value="__('Related Record')" />
            <select id="taskable_id" name="taskable_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">{{ __('Select record…') }}</option>
                @foreach ($taskableOptions ?? [] as $type => $options)
                    <optgroup label="{{ ucfirst($type === 'opportunity' ? crm_term('deal') : crm_term($type)) }}">
                        @foreach ($options as $option)
                            <option value="{{ $option['id'] }}" @selected(old('taskable_id', $currentTaskableId) == $option['id'] && old('taskable_type', $currentTaskableType) === $type)>{{ $option['label'] }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('taskable_id')" class="mt-2" />
        </div>
    @elseif ($currentTaskableType)
        <input type="hidden" name="taskable_type" value="{{ $currentTaskableType }}">
        <input type="hidden" name="taskable_id" value="{{ $currentTaskableId }}">
    @endif
</div>
