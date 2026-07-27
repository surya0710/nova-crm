@php
    $state = $attendance['state'] ?? 'not_checked_in';
    $working = $attendance['working_hours'] ?? [];
    $shiftInfo = $attendance['shift_info'] ?? [];
    $indicator = $attendance['indicator'] ?? null;
    $actions = $attendance['actions'] ?? [];
    $indicatorColors = [
        'green' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        'yellow' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'orange' => 'bg-orange-50 text-orange-800 ring-orange-200',
        'red' => 'bg-red-50 text-red-800 ring-red-200',
    ];
    $stateColors = [
        'checked_in' => 'text-emerald-700',
        'checked_out' => 'text-ink-heading',
        'on_leave' => 'text-amber-700',
        'holiday' => 'text-primary-700',
        'weekend' => 'text-ink-muted',
        'not_checked_in' => 'text-ink-muted',
    ];
@endphp

<x-ui.card class="md:col-span-2">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-medium uppercase tracking-wide text-ink-muted">{{ __('Today') }}</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <p class="text-xl font-semibold {{ $stateColors[$state] ?? 'text-ink-heading' }}">
                    {{ $attendance['state_label'] ?? __('Not Checked In') }}
                </p>
                @if ($indicator)
                    <span @class([
                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
                        $indicatorColors[$indicator['color']] ?? 'bg-surface-muted text-ink-muted ring-line',
                    ])>
                        {{ $indicator['label'] }}
                    </span>
                @endif
            </div>

            @if (!empty($working['clock_in_at']))
                <p class="mt-2 text-sm text-ink-muted">
                    {{ __('Check-in') }}: {{ \Carbon\Carbon::parse($working['clock_in_at'])->format('g:i A') }}
                    @if (!empty($working['clock_out_at']))
                        · {{ __('Check-out') }}: {{ \Carbon\Carbon::parse($working['clock_out_at'])->format('g:i A') }}
                    @endif
                </p>
            @endif

            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3">
                <div>
                    <p class="text-xs text-ink-muted">{{ __('Working Time') }}</p>
                    <p
                        class="text-lg font-semibold text-ink-heading"
                        @if (!empty($working['is_live']))
                            x-data="liveWorkingTimer(@js($working['worked_minutes']))"
                            x-text="label"
                        @endif
                    >
                        {{ $working['worked_label'] ?? '0m' }}
                    </p>
                </div>
                @if (!empty($shiftInfo['available']))
                    <div>
                        <p class="text-xs text-ink-muted">{{ __('Shift') }}</p>
                        <p class="text-sm font-medium text-ink-heading">{{ $shiftInfo['name'] }}</p>
                        <p class="text-xs text-ink-muted">{{ $shiftInfo['start_time'] }} – {{ $shiftInfo['end_time'] }}</p>
                    </div>
                @endif
                @if (!empty($working['expected_label']))
                    <div>
                        <p class="text-xs text-ink-muted">{{ __('Expected') }}</p>
                        <p class="text-sm font-medium text-ink-heading">{{ $working['expected_label'] }}</p>
                        @if (!empty($working['remaining_label']) && ($actions['can_check_out'] ?? false))
                            <p class="text-xs text-ink-muted">{{ __('Remaining') }}: {{ $working['remaining_label'] }}</p>
                        @endif
                    </div>
                @endif
            </div>

            @if (!empty($shiftInfo['branch']))
                <p class="mt-2 text-xs text-ink-muted">{{ __('Branch') }}: {{ $shiftInfo['branch'] }}</p>
            @endif
        </div>

        <div class="flex shrink-0 flex-col gap-2">
            @if ($actions['can_check_in'] ?? false)
                <form method="POST" action="{{ $actions['check_in_url'] }}">
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ route('ess.dashboard') }}">
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Check In') }}</x-ui.button>
                </form>
            @elseif ($actions['can_check_out'] ?? false)
                <form method="POST" action="{{ $actions['check_out_url'] }}">
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ route('ess.dashboard') }}">
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Check Out') }}</x-ui.button>
                </form>
            @elseif (!empty($actions['blocked_reason']))
                <p class="max-w-xs text-xs text-ink-muted">{{ $actions['blocked_reason'] }}</p>
            @endif
            <a href="{{ route('ess.attendance.index') }}" class="text-center text-xs text-primary-700 hover:underline">
                {{ __('View attendance history') }}
            </a>
        </div>
    </div>
</x-ui.card>

@once
<script>
function liveWorkingTimer(initialMinutes) {
    return {
        minutes: initialMinutes || 0,
        label: '',
        init() {
            this.render();
            setInterval(() => {
                this.minutes += 1;
                this.render();
            }, 60000);
        },
        render() {
            const hours = Math.floor(this.minutes / 60);
            const mins = this.minutes % 60;
            this.label = hours > 0 ? `${hours}h ${String(mins).padStart(2, '0')}m` : `${mins}m`;
        },
    };
}
</script>
@endonce
