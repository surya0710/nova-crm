@php
    $selectedDays = old('working_days', $calendar->working_days ?? []);
    if (! is_array($selectedDays)) {
        $selectedDays = [];
    }
@endphp

<div class="space-y-5">
    <div>
        <x-input-label for="employee_id" :value="__('Employee')" />
        <select id="employee_id" name="employee_id" required class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
            <option value="">{{ __('Select employee') }}</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" @selected((int) old('employee_id', $calendar->employee_id) === $employee->id)>{{ $employee->full_name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('employee_id')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="working_hours_per_day" :value="__('Hours per day')" />
            <x-text-input id="working_hours_per_day" name="working_hours_per_day" type="number" step="0.25" min="0.25" max="24" class="mt-1 block w-full" :value="old('working_hours_per_day', $calendar->working_hours_per_day)" required />
            <x-input-error :messages="$errors->get('working_hours_per_day')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="timezone" :value="__('Timezone')" />
            <x-text-input id="timezone" name="timezone" class="mt-1 block w-full" :value="old('timezone', $calendar->timezone)" />
            <x-input-error :messages="$errors->get('timezone')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label :value="__('Working days')" />
        <div class="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-2">
            @foreach ($weekdays as $day)
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="working_days[]" value="{{ $day }}" @checked(in_array($day, $selectedDays, true)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    {{ __(ucfirst($day)) }}
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('working_days')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="effective_from" :value="__('Effective from')" />
            <x-text-input id="effective_from" name="effective_from" type="date" class="mt-1 block w-full" :value="old('effective_from', optional($calendar->effective_from)->toDateString())" required />
            <x-input-error :messages="$errors->get('effective_from')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="effective_to" :value="__('Effective to')" />
            <x-text-input id="effective_to" name="effective_to" type="date" class="mt-1 block w-full" :value="old('effective_to', optional($calendar->effective_to)->toDateString())" />
            <x-input-error :messages="$errors->get('effective_to')" class="mt-2" />
        </div>
    </div>
</div>
