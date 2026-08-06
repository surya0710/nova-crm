@props([
    'task',
    'recurrence' => null,
])

@php
    $recurrence = $recurrence ?? $task->recurrence;
    $types = \App\Services\TaskRecurrenceService::TYPES;
    $endTypes = \App\Services\TaskRecurrenceService::END_TYPES;
    $dayLabels = [
        0 => __('Sun'),
        1 => __('Mon'),
        2 => __('Tue'),
        3 => __('Wed'),
        4 => __('Thu'),
        5 => __('Fri'),
        6 => __('Sat'),
    ];
    $selectedDays = old('days_of_week', $recurrence?->days_of_week ?? []);
    if (! is_array($selectedDays)) {
        $selectedDays = [];
    }
@endphp

<div class="space-y-4" x-data="{
    endType: @js(old('end_type', $recurrence?->end_type ?? 'never')),
    recurrenceType: @js(old('recurrence_type', $recurrence?->recurrence_type ?? 'weekly')),
}">
    @if ($recurrence)
        <form method="POST" action="{{ route('tasks.recurrence.update', [$task, $recurrence]) }}" class="space-y-4">
            @csrf
            @method('PATCH')
    @else
        <form method="POST" action="{{ route('tasks.recurrence.store', $task) }}" class="space-y-4">
            @csrf
    @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="recurrence_type" :value="__('Recurrence type')" />
                <select id="recurrence_type" name="recurrence_type" x-model="recurrenceType" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" required>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" @selected(old('recurrence_type', $recurrence?->recurrence_type) === $type)>{{ __(ucfirst($type)) }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('recurrence_type')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="interval" :value="__('Interval')" />
                <x-text-input id="interval" type="number" name="interval" min="1" max="365" class="block mt-1 w-full" :value="old('interval', $recurrence?->interval ?? 1)" />
                <x-input-error :messages="$errors->get('interval')" class="mt-1" />
            </div>
        </div>

        <div x-show="recurrenceType === 'weekly' || recurrenceType === 'custom'" x-cloak>
            <x-input-label :value="__('Days of week')" />
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($dayLabels as $value => $label)
                    <label class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs text-slate-700">
                        <input
                            type="checkbox"
                            name="days_of_week[]"
                            value="{{ $value }}"
                            @checked(in_array($value, $selectedDays, false) || in_array((string) $value, $selectedDays, true))
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('days_of_week')" class="mt-1" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="end_type" :value="__('Ends')" />
                <select id="end_type" name="end_type" x-model="endType" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" required>
                    @foreach ($endTypes as $type)
                        <option value="{{ $type }}" @selected(old('end_type', $recurrence?->end_type) === $type)>{{ __(ucfirst($type)) }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('end_type')" class="mt-1" />
            </div>

            <div x-show="endType === 'date'" x-cloak>
                <x-input-label for="end_date" :value="__('End date')" />
                <x-text-input id="end_date" type="date" name="end_date" class="block mt-1 w-full" :value="old('end_date', optional($recurrence?->end_date)->format('Y-m-d'))" />
                <x-input-error :messages="$errors->get('end_date')" class="mt-1" />
            </div>

            <div x-show="endType === 'occurrences'" x-cloak>
                <x-input-label for="occurrences" :value="__('Occurrences')" />
                <x-text-input id="occurrences" type="number" name="occurrences" min="1" class="block mt-1 w-full" :value="old('occurrences', $recurrence?->occurrences)" />
                <x-input-error :messages="$errors->get('occurrences')" class="mt-1" />
            </div>
        </div>

        <div class="flex flex-wrap gap-4">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="skip_holidays" value="0" />
                <input type="checkbox" name="skip_holidays" value="1" @checked(old('skip_holidays', $recurrence?->skip_holidays)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                {{ __('Skip holidays') }}
            </label>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="copy_attachments" value="0" />
                <input type="checkbox" name="copy_attachments" value="1" @checked(old('copy_attachments', $recurrence?->copy_attachments)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                {{ __('Copy attachments') }}
            </label>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="is_active" value="0" />
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $recurrence?->is_active ?? true)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                {{ __('Active') }}
            </label>
        </div>

        @if ($recurrence)
            <div class="text-xs text-slate-500">
                @if ($recurrence->next_run_at)
                    {{ __('Next run') }}: {{ $recurrence->next_run_at->format('M j, Y g:i A') }}
                @endif
                @if ($recurrence->generated_count)
                    · {{ __('Generated') }}: {{ $recurrence->generated_count }}
                @endif
            </div>
        @endif

        <div class="flex items-center gap-3">
            <x-primary-button type="submit">
                {{ $recurrence ? __('Update recurrence') : __('Enable recurrence') }}
            </x-primary-button>
        </div>
    </form>

    @if ($recurrence)
        @can('delete', $recurrence)
            <form method="POST" action="{{ route('tasks.recurrence.destroy', [$task, $recurrence]) }}" onsubmit="return confirm('{{ __('Remove recurrence from this task?') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm text-red-600 hover:text-red-800">{{ __('Remove recurrence') }}</button>
            </form>
        @endcan
    @endif
</div>
