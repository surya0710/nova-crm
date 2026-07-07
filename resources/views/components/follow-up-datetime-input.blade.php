@props([
    'value' => null,
    'min' => null,
    'timezone' => null,
    'showQuickPick' => false,
])

@php
    $service = app(\App\Services\LeadFollowUpService::class);
    $minCarbon = $service->organizationNow()->copy()->addMinute();
    $minValue = $min ?? $service->minInputValue();
    $timezone = $timezone ?? $service->organizationTimezone();
    $inputId = $attributes->get('id', 'next_follow_up_at');
    $datePart = $value ? substr($value, 0, 10) : '';
    $timePart = $value ? substr($value, 11, 5) : '';
    $minDate = substr($minValue, 0, 10);
    $minTime = substr($minValue, 11, 5);
    $quickPickValues = [
        'hour' => $service->formatForInput($service->organizationNow()->copy()->addHour()),
        'three_hours' => $service->formatForInput($service->organizationNow()->copy()->addHours(3)),
        'tomorrow' => $service->formatForInput($service->organizationNow()->copy()->addDay()->setTime(10, 0)),
    ];
@endphp

<div
    {{ $attributes->only('class')->merge(['class' => 'space-y-2']) }}
    x-data="followUpDatetime({
        date: @js($datePart),
        time: @js($timePart),
        minDate: @js($minDate),
        minTime: @js($minTime),
    })"
>
    <input type="hidden" name="next_follow_up_at" :value="combined" />

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label for="{{ $inputId }}_date" class="sr-only">{{ __('Date') }}</label>
            <input
                type="date"
                id="{{ $inputId }}_date"
                x-model="date"
                :min="minDate"
                class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
            />
        </div>
        <div>
            <label for="{{ $inputId }}_time" class="sr-only">{{ __('Time') }}</label>
            <input
                type="time"
                id="{{ $inputId }}_time"
                x-model="time"
                :min="timeMin"
                step="60"
                class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
            />
        </div>
    </div>

    <p class="text-xs text-slate-500">
        {{ __('Date and time are in your organization timezone (:timezone). Follow-up must be after :time.', [
            'timezone' => $timezone,
            'time' => $service->organizationNow()->format('M j, Y g:i A'),
        ]) }}
    </p>

    @if ($showQuickPick)
        <div class="flex flex-wrap gap-2">
            <button type="button" class="text-xs font-medium px-2.5 py-1.5 rounded-lg border border-indigo-200 text-indigo-700 hover:bg-indigo-50 transition" @click="applyQuickPick(@js($quickPickValues['hour']))">
                {{ __('In 1 hour') }}
            </button>
            <button type="button" class="text-xs font-medium px-2.5 py-1.5 rounded-lg border border-indigo-200 text-indigo-700 hover:bg-indigo-50 transition" @click="applyQuickPick(@js($quickPickValues['three_hours']))">
                {{ __('In 3 hours') }}
            </button>
            <button type="button" class="text-xs font-medium px-2.5 py-1.5 rounded-lg border border-indigo-200 text-indigo-700 hover:bg-indigo-50 transition" @click="applyQuickPick(@js($quickPickValues['tomorrow']))">
                {{ __('Tomorrow 10 AM') }}
            </button>
        </div>
    @endif
</div>
