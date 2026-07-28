@php
    $prev = \Carbon\Carbon::create($year, $month, 1)->subMonth();
    $next = \Carbon\Carbon::create($year, $month, 1)->addMonth();
    $routeParams = array_filter([
        'year' => $prev->year,
        'month' => $prev->month,
        'view' => $view ?? null,
        'employee_id' => $employee?->id ?? null,
    ]);
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Attendance Calendar')"
        :subtitle="$calendar['month_label'] ?? \Carbon\Carbon::create($year, $month, 1)->format('F Y')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Attendance'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.attendance.index', ['year' => $prev->year, 'month' => $prev->month, 'view' => $view ?? 'my', 'employee_id' => $employee?->id])" variant="secondary" size="sm">← {{ $prev->format('M Y') }}</x-ui.button>
            <x-ui.button :href="route('hrms.attendance.index', ['year' => now()->year, 'month' => now()->month, 'view' => $view ?? 'my', 'employee_id' => $employee?->id])" variant="secondary" size="sm">{{ __('Today') }}</x-ui.button>
            <x-ui.button :href="route('hrms.attendance.index', ['year' => $next->year, 'month' => $next->month, 'view' => $view ?? 'my', 'employee_id' => $employee?->id])" variant="secondary" size="sm">{{ $next->format('M Y') }} →</x-ui.button>
            <x-ui.button :href="route('hrms.attendance.records')" variant="secondary" size="sm">{{ __('Records') }}</x-ui.button>
            <x-ui.button :href="route('hrms.attendance.summary')" variant="secondary" size="sm">{{ __('Daily Summary') }}</x-ui.button>
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
                @if ($canFilterEmployees && $employees->isNotEmpty())
                    <x-forms.field :label="__('Employee')" name="employee_id" class="mb-0">
                        <x-forms.select name="employee_id">
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}" @selected(($employee?->id ?? null) === $emp->id)>{{ $emp->full_name }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                @endif
                @if ($canViewTeam)
                    <x-forms.field :label="__('View')" name="view" class="mb-0">
                        <x-forms.select name="view">
                            <option value="my" @selected(($view ?? 'my') === 'my')>{{ __('My Calendar') }}</option>
                            <option value="team" @selected(($view ?? 'my') === 'team')>{{ __('Team Calendar') }}</option>
                        </x-forms.select>
                    </x-forms.field>
                @endif
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Apply') }}</x-ui.button>
            </form>
        </x-slot:filters>

        @if (($mode ?? 'employee') === 'team')
            <x-ui.card class="mb-6">
                <x-entity.section :title="__('Team Calendar')">
                    <div class="space-y-4">
                        @forelse ($calendar['members'] ?? [] as $member)
                            <div class="rounded-lg border border-line p-4">
                                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <p class="font-medium text-ink-heading">{{ $member['employee']['name'] }}</p>
                                        <p class="text-xs text-ink-muted">{{ $member['employee']['code'] ?? '' }}</p>
                                    </div>
                                    <x-ui.button
                                        :href="route('hrms.attendance.index', ['year' => $year, 'month' => $month, 'employee_id' => $member['employee']['id']])"
                                        variant="ghost"
                                        size="sm"
                                    >{{ __('View Calendar') }}</x-ui.button>
                                </div>
                                <div class="grid grid-cols-7 gap-1">
                                    @foreach ($member['days'] as $day)
                                        @php
                                            $color = match ($day['visual']['color'] ?? 'default') {
                                                'emerald' => 'bg-emerald-400',
                                                'red' => 'bg-red-400',
                                                'blue' => 'bg-blue-400',
                                                'purple' => 'bg-purple-400',
                                                'orange' => 'bg-orange-400',
                                                'slate' => 'bg-slate-300',
                                                'amber', 'yellow' => 'bg-amber-400',
                                                'cyan' => 'bg-cyan-400',
                                                default => 'bg-surface-muted',
                                            };
                                        @endphp
                                        <span class="h-2 rounded-full {{ $color }}" title="{{ $day['date'] }} · {{ $day['visual']['label'] ?? '' }}"></span>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <x-ui.empty-state-preset variant="attendance" :title="__('No team members found.')" />
                        @endforelse
                    </div>
                </x-entity.section>
            </x-ui.card>
        @else
            <div class="mb-6">
                <x-attendance.summary-cards :summary="$calendar['summary'] ?? []" />
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <x-ui.card>
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="font-medium text-ink-heading">{{ $calendar['employee']['name'] ?? $employee?->full_name }}</p>
                                <p class="text-sm text-ink-muted">{{ $calendar['month_label'] ?? '' }}</p>
                            </div>
                        </div>
                        <x-attendance.calendar-grid
                            :days="$calendar['days'] ?? []"
                            :year="$year"
                            :month="$month"
                        />
                        <div class="mt-4 border-t border-line pt-4">
                            <x-attendance.calendar-legend :items="$calendar['legend'] ?? []" />
                        </div>
                    </x-ui.card>
                </div>

                <div class="space-y-6">
                    <x-ui.card>
                        <x-entity.section :title="__('Leave Balances')">
                            @forelse ($calendar['leave_balances'] ?? [] as $balance)
                                <div class="flex justify-between border-b border-line py-2 text-sm last:border-0">
                                    <span class="text-ink-heading">{{ $balance['leave_type'] }}</span>
                                    <span class="font-medium text-ink-heading">{{ $balance['balance'] }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-ink-muted">{{ __('No leave balances.') }}</p>
                            @endforelse
                            <div class="mt-4">
                                <x-ui.button :href="route('ess.leave.index')" variant="secondary" size="sm">{{ __('Apply Leave') }}</x-ui.button>
                            </div>
                        </x-entity.section>
                    </x-ui.card>

                    <x-ui.card>
                        <x-entity.section :title="__('Recent Timeline')">
                            @forelse ($calendar['timeline'] ?? [] as $entry)
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
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
