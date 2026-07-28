@php
    $prev = \Carbon\Carbon::create($year, $month, 1)->subMonth();
    $next = \Carbon\Carbon::create($year, $month, 1)->addMonth();
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('My Attendance')"
        :subtitle="$calendar['month_label']"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('My HR'), 'href' => route('ess.dashboard')],
                ['label' => __('Attendance'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('ess.attendance.index', ['year' => $prev->year, 'month' => $prev->month])" variant="secondary" size="sm">← {{ $prev->format('M Y') }}</x-ui.button>
            <x-ui.button :href="route('ess.attendance.index', ['year' => now()->year, 'month' => now()->month])" variant="secondary" size="sm">{{ __('Today') }}</x-ui.button>
            <x-ui.button :href="route('ess.attendance.index', ['year' => $next->year, 'month' => $next->month])" variant="secondary" size="sm">{{ $next->format('M Y') }} →</x-ui.button>
            <x-ui.button :href="route('ess.attendance.records')" variant="secondary" size="sm">{{ __('History') }}</x-ui.button>
            <x-ui.button :href="route('ess.leave.index')" variant="secondary" size="sm">{{ __('Apply Leave') }}</x-ui.button>
        </x-slot:actions>

        <x-slot:filters>
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <x-forms.field :label="__('Month')" name="month" class="mb-0">
                    <x-forms.select name="month">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($m === $month)>{{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}</option>
                        @endfor
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Year')" name="year" class="mb-0">
                    <x-forms.input type="number" name="year" :value="$year" min="2020" max="2100" />
                </x-forms.field>
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Go') }}</x-ui.button>
            </form>
        </x-slot:filters>

        @include('ess.partials.nav')

        <x-ui.card class="mb-6">
            <div class="flex flex-wrap gap-3">
                @if (!$todayRecord || !$todayRecord->clock_in_at)
                    <form method="POST" action="{{ route('ess.attendance.clock-in') }}">
                        @csrf
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Check In') }}</x-ui.button>
                    </form>
                @endif
                @if ($todayRecord && $todayRecord->clock_in_at && !$todayRecord->clock_out_at)
                    <form method="POST" action="{{ route('ess.attendance.clock-out') }}">
                        @csrf
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Check Out') }}</x-ui.button>
                    </form>
                @endif
            </div>
        </x-ui.card>

        <div class="mb-6">
            <x-attendance.summary-cards :summary="$calendar['summary']" />
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <x-ui.card>
                    <x-attendance.calendar-grid
                        :days="$calendar['days']"
                        :year="$year"
                        :month="$month"
                    />
                    <div class="mt-4 border-t border-line pt-4">
                        <x-attendance.calendar-legend :items="$calendar['legend']" />
                    </div>
                </x-ui.card>
            </div>

            <div class="space-y-6">
                <x-ui.card>
                    <x-entity.section :title="__('Leave Balances')">
                        @forelse ($calendar['leave_balances'] as $balance)
                            <div class="flex justify-between border-b border-line py-2 text-sm last:border-0">
                                <span class="text-ink-heading">{{ $balance['leave_type'] }}</span>
                                <span class="font-medium text-ink-heading">{{ $balance['balance'] }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-ink-muted">{{ __('No leave balances.') }}</p>
                        @endforelse
                    </x-entity.section>
                </x-ui.card>

                <x-ui.card>
                    <x-entity.section :title="__('Recent Timeline')">
                        @forelse ($calendar['timeline'] as $entry)
                            <div class="flex items-start justify-between border-b border-line py-2 text-sm last:border-0">
                                <div>
                                    <p class="font-medium text-ink-heading">{{ $entry['date'] }}</p>
                                    <p class="text-ink-muted">{{ $entry['label'] }}</p>
                                </div>
                                @if (!empty($entry['time']))
                                    <span class="text-ink-muted">{{ $entry['time'] }}</span>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-ink-muted">{{ __('No recent attendance.') }}</p>
                        @endforelse
                    </x-entity.section>
                </x-ui.card>
            </div>
        </div>
    </x-layouts.entity-listing>
</x-app-layout>
