@props([
    'days' => [],
    'year' => null,
    'month' => null,
    'compact' => false,
])

@php
    $colorClasses = [
        'emerald' => 'bg-emerald-50 border-emerald-200 hover:bg-emerald-100',
        'red' => 'bg-red-50 border-red-200 hover:bg-red-100',
        'blue' => 'bg-blue-50 border-blue-200 hover:bg-blue-100',
        'purple' => 'bg-purple-50 border-purple-200 hover:bg-purple-100',
        'orange' => 'bg-orange-50 border-orange-200 hover:bg-orange-100',
        'slate' => 'bg-slate-100 border-slate-200 hover:bg-slate-200/70',
        'amber' => 'bg-amber-50 border-amber-200 hover:bg-amber-100',
        'cyan' => 'bg-cyan-50 border-cyan-200 hover:bg-cyan-100',
        'yellow' => 'bg-yellow-50 border-yellow-200 hover:bg-yellow-100',
        'neutral' => 'bg-neutral-100 border-neutral-300 hover:bg-neutral-200',
        'default' => 'bg-surface-card border-line hover:bg-surface-muted',
    ];

    $borderClasses = [
        'red' => 'ring-2 ring-red-400',
        'slate' => 'ring-2 ring-slate-300',
    ];

    $start = \Carbon\Carbon::create($year, $month, 1);
    $padding = $start->dayOfWeek; // Sunday = 0
    $weekdays = [__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')];
@endphp

<div
    x-data="{
        selectedDay: null,
        openDay(day) {
            this.selectedDay = day;
            window.dispatchEvent(new CustomEvent('open-drawer-attendance-day'));
        },
    }"
>
    <div class="mb-2 grid grid-cols-7 gap-1 text-center text-xs font-medium text-ink-muted">
        @foreach ($weekdays as $weekday)
            <div class="py-2">{{ $weekday }}</div>
        @endforeach
    </div>

    <div class="grid grid-cols-7 gap-1">
        @for ($i = 0; $i < $padding; $i++)
            <div class="min-h-[4.5rem] rounded-lg border border-transparent bg-transparent"></div>
        @endfor

        @foreach ($days as $day)
            @php
                $visual = $day['visual'];
                $cellClass = $colorClasses[$visual['color']] ?? $colorClasses['default'];
                $borderClass = isset($visual['border']) ? ($borderClasses[$visual['border']] ?? '') : '';
                $todayRing = ($day['is_today'] ?? false) ? 'ring-2 ring-primary-400 ring-offset-1' : '';
            @endphp
            <button
                type="button"
                @click="openDay(@js($day))"
                class="min-h-[4.5rem] rounded-lg border p-2 text-left transition {{ $cellClass }} {{ $borderClass }} {{ $todayRing }}"
            >
                <div class="flex items-start justify-between gap-1">
                    <span class="text-sm font-semibold text-ink-heading">{{ $day['day'] }}</span>
                    @if (!empty($visual['dots']))
                        <span class="flex gap-0.5">
                            @foreach ($visual['dots'] as $dot)
                                <span class="h-1.5 w-1.5 rounded-full {{ $dot === 'late' ? 'bg-yellow-500' : 'bg-neutral-800' }}"></span>
                            @endforeach
                        </span>
                    @endif
                </div>
                @unless ($compact)
                    <p class="mt-1 truncate text-[10px] font-medium leading-tight text-ink-muted">{{ $visual['label'] }}</p>
                    @if (!empty($day['attendance']['clock_in']))
                        <p class="truncate text-[10px] text-ink-muted">{{ $day['attendance']['clock_in'] }}</p>
                    @endif
                    @if (!empty($day['attendance']['working_label']) && $day['attendance']['working_label'] !== '0m')
                        <p class="truncate text-[10px] text-ink-muted">{{ $day['attendance']['working_label'] }}</p>
                    @endif
                @endunless
            </button>
        @endforeach
    </div>

    <x-ui.drawer name="attendance-day" :title="__('Day Details')">
        <template x-if="selectedDay">
            <div class="space-y-4 text-sm">
                <div>
                    <p class="text-lg font-semibold text-ink-heading" x-text="new Date(selectedDay.date + 'T00:00:00').toLocaleDateString(undefined, { day: 'numeric', month: 'long', year: 'numeric' })"></p>
                    <p class="text-ink-muted" x-text="selectedDay.visual?.label"></p>
                </div>

                <template x-if="selectedDay.holiday">
                    <div class="rounded-lg border border-orange-200 bg-orange-50 p-3">
                        <p class="font-medium text-orange-900" x-text="selectedDay.holiday.name"></p>
                        <p class="text-orange-700" x-text="selectedDay.holiday.type"></p>
                    </div>
                </template>

                <template x-if="selectedDay.leave">
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 space-y-1">
                        <p class="font-medium text-blue-900" x-text="selectedDay.leave.type"></p>
                        <p class="text-blue-700" x-text="selectedDay.leave.status_label"></p>
                        <p class="text-blue-700" x-show="selectedDay.leave.reason" x-text="selectedDay.leave.reason"></p>
                    </div>
                </template>

                <template x-if="selectedDay.attendance">
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs text-ink-muted">{{ __('Check In') }}</p>
                                <p class="font-medium text-ink-heading" x-text="selectedDay.attendance.clock_in || '—'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-ink-muted">{{ __('Check Out') }}</p>
                                <p class="font-medium text-ink-heading" x-text="selectedDay.attendance.clock_out || '—'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-ink-muted">{{ __('Working Hours') }}</p>
                                <p class="font-medium text-ink-heading" x-text="selectedDay.attendance.working_label || '—'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-ink-muted">{{ __('Status') }}</p>
                                <p class="font-medium text-ink-heading" x-text="selectedDay.attendance.status_label"></p>
                            </div>
                        </div>
                        <template x-if="selectedDay.shift">
                            <div>
                                <p class="text-xs text-ink-muted">{{ __('Shift') }}</p>
                                <p class="font-medium text-ink-heading" x-text="selectedDay.shift.name"></p>
                            </div>
                        </template>
                        <template x-if="selectedDay.indicator">
                            <div>
                                <p class="text-xs text-ink-muted">{{ __('Indicator') }}</p>
                                <p class="font-medium text-ink-heading" x-text="selectedDay.indicator.label"></p>
                            </div>
                        </template>
                        <template x-if="selectedDay.attendance.notes">
                            <div>
                                <p class="text-xs text-ink-muted">{{ __('Notes') }}</p>
                                <p class="text-ink-heading" x-text="selectedDay.attendance.notes"></p>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!selectedDay.attendance && !selectedDay.leave && !selectedDay.holiday">
                    <p class="text-ink-muted">{{ __('No attendance details for this day.') }}</p>
                </template>
            </div>
        </template>
    </x-ui.drawer>
</div>
