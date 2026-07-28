@props([
    'calendar' => [],
    'year' => null,
    'month' => null,
    'mode' => 'employee',
    'view' => 'my',
    'employeeId' => null,
    'employees' => null,
    'canViewTeam' => false,
    'canFilterEmployees' => false,
    'navigation' => [],
    'apiUrl' => '',
])

@php
    $monthNames = collect(range(1, 12))->mapWithKeys(fn (int $m) => [
        $m => \Carbon\Carbon::create(null, $m, 1)->format('F'),
    ])->all();
    $summaryCards = [
        ['key' => 'present', 'label' => __('Present')],
        ['key' => 'leave_approved', 'label' => __('Leave')],
        ['key' => 'absent', 'label' => __('Absent')],
        ['key' => 'holiday', 'label' => __('Holiday')],
        ['key' => 'weekend', 'label' => __('Weekend')],
        ['key' => 'late', 'label' => __('Late')],
        ['key' => 'half_day', 'label' => __('Half Day')],
    ];
    $weekdays = [__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')];
@endphp

<div
    x-data="attendanceCalendar({
        apiUrl: @js($apiUrl),
        year: @js((int) $year),
        month: @js((int) $month),
        mode: @js($mode),
        view: @js($view),
        employeeId: @js($employeeId),
        calendar: @js($calendar),
        navigation: @js($navigation),
        monthNames: @js($monthNames),
    })"
    x-init="init()"
    class="relative"
>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div class="flex flex-wrap items-end gap-2">
            <button
                type="button"
                class="inline-flex items-center justify-center rounded-md border border-line bg-surface-muted px-3 py-1.5 text-xs font-semibold text-ink-heading hover:bg-neutral-100 disabled:pointer-events-none disabled:opacity-50"
                @click="previousMonth()"
                x-bind:disabled="!canGoPrevious"
            >
                <span aria-hidden="true">‹</span>
            </button>

            <x-forms.field :label="__('Month')" class="mb-0 min-w-[9rem]">
                <select
                    x-model.number="month"
                    @change="loadCalendar()"
                    class="block w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <template x-for="(label, value) in monthNames" :key="value">
                        <option :value="Number(value)" x-text="label"></option>
                    </template>
                </select>
            </x-forms.field>

            <x-forms.field :label="__('Year')" class="mb-0 min-w-[7rem]">
                <select
                    x-model.number="year"
                    @change="loadCalendar()"
                    class="block w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <template x-for="y in years" :key="y">
                        <option :value="y" x-text="y"></option>
                    </template>
                </select>
            </x-forms.field>

            <x-ui.button type="button" variant="secondary" size="sm" @click="goToday()">{{ __('Today') }}</x-ui.button>

            <button
                type="button"
                class="inline-flex items-center justify-center rounded-md border border-line bg-surface-muted px-3 py-1.5 text-xs font-semibold text-ink-heading hover:bg-neutral-100 disabled:pointer-events-none disabled:opacity-50"
                @click="nextMonth()"
                x-bind:disabled="!canGoNext"
            >
                <span aria-hidden="true">›</span>
            </button>
        </div>

        <p class="text-sm font-medium text-ink-heading" x-text="calendar.month_label || ''"></p>
    </div>

    @if (($canFilterEmployees && ($employees?->isNotEmpty() ?? false)) || $canViewTeam)
        <div class="mb-6 flex flex-wrap items-end gap-3">
            @if ($canFilterEmployees && ($employees?->isNotEmpty() ?? false))
                <x-forms.field :label="__('Employee')" class="mb-0 min-w-[12rem]">
                    <select
                        x-model.number="employeeId"
                        @change="onFilterChange()"
                        class="block w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                        @endforeach
                    </select>
                </x-forms.field>
            @endif
            @if ($canViewTeam)
                <x-forms.field :label="__('View')" class="mb-0 min-w-[10rem]">
                    <select
                        x-model="view"
                        @change="onFilterChange()"
                        class="block w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="my">{{ __('My Calendar') }}</option>
                        <option value="team">{{ __('Team Calendar') }}</option>
                    </select>
                </x-forms.field>
            @endif
        </div>
    @endif

    <div x-show="loading" x-cloak class="absolute inset-0 z-10 flex items-center justify-center rounded-lg bg-surface-card/70">
        <p class="text-sm text-ink-muted">{{ __('Loading calendar…') }}</p>
    </div>

    <template x-if="mode === 'team'">
        <x-ui.card class="mb-6">
            <x-entity.section :title="__('Team Calendar')">
                <div class="space-y-4">
                    <template x-for="member in (calendar.members || [])" :key="member.employee.id">
                        <div class="rounded-lg border border-line p-4">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="font-medium text-ink-heading" x-text="member.employee.name"></p>
                                    <p class="text-xs text-ink-muted" x-text="member.employee.code || ''"></p>
                                </div>
                                <button
                                    type="button"
                                    class="text-sm font-medium text-primary-600 hover:text-primary-700"
                                    @click="viewMember(member.employee.id)"
                                >{{ __('View Calendar') }}</button>
                            </div>
                            <div class="grid grid-cols-7 gap-1">
                                <template x-for="day in member.days" :key="day.date">
                                    <span
                                        class="h-2 rounded-full"
                                        :class="dotColor(day.visual?.color)"
                                        :title="`${day.date} · ${day.visual?.label || ''}`"
                                    ></span>
                                </template>
                            </div>
                        </div>
                    </template>
                    <template x-if="!(calendar.members || []).length">
                        <x-ui.empty-state-preset variant="attendance" :title="__('No team members found.')" />
                    </template>
                </div>
            </x-entity.section>
        </x-ui.card>
    </template>

    <template x-if="mode !== 'team'">
        <div>
            <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                @foreach ($summaryCards as $card)
                    <div class="rounded-xl border border-line bg-surface-card p-4 shadow-sm">
                        <p class="text-xs text-ink-muted">{{ $card['label'] }}</p>
                        <p class="mt-1 text-2xl font-semibold text-ink-heading" x-text="summaryValue('{{ $card['key'] }}')"></p>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <x-ui.card>
                        <div class="mb-4">
                            <p class="font-medium text-ink-heading" x-text="calendar.employee?.name || ''"></p>
                            <p class="text-sm text-ink-muted" x-text="calendar.month_label || ''"></p>
                        </div>

                        <div class="mb-2 grid grid-cols-7 gap-1 text-center text-xs font-medium text-ink-muted">
                            @foreach ($weekdays as $weekday)
                                <div class="py-2">{{ $weekday }}</div>
                            @endforeach
                        </div>

                        <div class="grid grid-cols-7 gap-1">
                            <template x-for="i in gridPadding" :key="'pad-' + i">
                                <div class="min-h-[4.5rem] rounded-lg border border-transparent"></div>
                            </template>
                            <template x-for="day in (calendar.days || [])" :key="day.date">
                                <button
                                    type="button"
                                    @click="openDay(day)"
                                    class="min-h-[4.5rem] rounded-lg border p-2 text-left transition"
                                    :class="cellClasses(day)"
                                >
                                    <div class="flex items-start justify-between gap-1">
                                        <span class="text-sm font-semibold text-ink-heading" x-text="day.day"></span>
                                        <span class="flex gap-0.5" x-show="(day.visual?.dots || []).length">
                                            <template x-for="dot in (day.visual?.dots || [])" :key="dot">
                                                <span class="h-1.5 w-1.5 rounded-full" :class="dot === 'late' ? 'bg-yellow-500' : 'bg-neutral-800'"></span>
                                            </template>
                                        </span>
                                    </div>
                                    <p class="mt-1 truncate text-[10px] font-medium leading-tight text-ink-muted" x-text="day.visual?.label"></p>
                                    <p class="truncate text-[10px] text-ink-muted" x-show="day.attendance?.clock_in" x-text="day.attendance?.clock_in"></p>
                                    <p class="truncate text-[10px] text-ink-muted" x-show="day.attendance?.working_label && day.attendance?.working_label !== '0m'" x-text="day.attendance?.working_label"></p>
                                </button>
                            </template>
                        </div>

                        <div class="mt-4 border-t border-line pt-4">
                            <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs text-ink-muted">
                                <template x-for="item in (calendar.legend || [])" :key="item.key">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="inline-flex h-3 w-3 rounded-full border" :class="legendSwatch(item.color)"></span>
                                        <span x-text="`${item.symbol || ''} ${item.label}`"></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                <div class="space-y-6">
                    <x-ui.card>
                        <x-entity.section :title="__('Leave Balances')">
                            <template x-for="balance in (calendar.leave_balances || [])" :key="balance.code + balance.leave_type">
                                <div class="flex justify-between border-b border-line py-2 text-sm last:border-0">
                                    <span class="text-ink-heading" x-text="balance.leave_type"></span>
                                    <span class="font-medium text-ink-heading" x-text="balance.balance"></span>
                                </div>
                            </template>
                            <template x-if="!(calendar.leave_balances || []).length">
                                <p class="text-sm text-ink-muted">{{ __('No leave balances.') }}</p>
                            </template>
                            <div class="mt-4">
                                <x-ui.button :href="route('ess.leave.index')" variant="secondary" size="sm">{{ __('Apply Leave') }}</x-ui.button>
                            </div>
                        </x-entity.section>
                    </x-ui.card>

                    <x-ui.card>
                        <x-entity.section :title="__('Recent Timeline')">
                            <template x-for="(entry, index) in (calendar.timeline || [])" :key="index">
                                <div class="flex items-start justify-between border-b border-line py-2 text-sm last:border-0">
                                    <div>
                                        <p class="font-medium text-ink-heading" x-text="entry.date"></p>
                                        <p class="text-ink-muted" x-text="entry.label"></p>
                                    </div>
                                    <span class="text-ink-muted" x-show="entry.time" x-text="entry.time"></span>
                                </div>
                            </template>
                            <template x-if="!(calendar.timeline || []).length">
                                <p class="text-sm text-ink-muted">{{ __('No recent attendance.') }}</p>
                            </template>
                        </x-entity.section>
                    </x-ui.card>
                </div>
            </div>
        </div>
    </template>

    <x-ui.drawer name="attendance-day" :title="__('Day Details')">
        <template x-if="selectedDay">
            <div class="space-y-4 text-sm">
                <div>
                    <p class="text-lg font-semibold text-ink-heading" x-text="formatDayTitle(selectedDay.date)"></p>
                    <p class="text-ink-muted" x-text="selectedDay.visual?.label"></p>
                </div>
                <template x-if="selectedDay.holiday">
                    <div class="rounded-lg border border-orange-200 bg-orange-50 p-3">
                        <p class="font-medium text-orange-900" x-text="selectedDay.holiday.name"></p>
                        <p class="text-orange-700" x-text="selectedDay.holiday.type"></p>
                    </div>
                </template>
                <template x-if="selectedDay.leave">
                    <div class="space-y-1 rounded-lg border border-blue-200 bg-blue-50 p-3">
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
                    </div>
                </template>
            </div>
        </template>
    </x-ui.drawer>
</div>

@once
    @push('page-assets')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('attendanceCalendar', (config) => ({
                    apiUrl: config.apiUrl,
                    year: config.year,
                    month: config.month,
                    mode: config.mode,
                    view: config.view,
                    employeeId: config.employeeId,
                    calendar: config.calendar,
                    navigation: config.navigation,
                    monthNames: config.monthNames,
                    years: config.navigation?.years || [],
                    minYear: config.navigation?.min_year,
                    maxYear: config.navigation?.max_year,
                    todayYear: config.navigation?.today_year,
                    todayMonth: config.navigation?.today_month,
                    loading: false,
                    selectedDay: null,
                    requestToken: 0,

                    init() {
                        this.syncYears();
                    },

                    get gridPadding() {
                        return new Date(this.year, this.month - 1, 1).getDay();
                    },

                    get canGoPrevious() {
                        if (this.month > 1) {
                            return true;
                        }

                        return this.year > this.minYear;
                    },

                    get canGoNext() {
                        if (this.month < 12) {
                            return true;
                        }

                        return this.year < this.maxYear;
                    },

                    summaryValue(key) {
                        return this.calendar?.summary?.[key] ?? 0;
                    },

                    syncYears() {
                        if (this.calendar?.navigation) {
                            this.navigation = this.calendar.navigation;
                            this.years = this.calendar.navigation.years || this.years;
                            this.minYear = this.calendar.navigation.min_year;
                            this.maxYear = this.calendar.navigation.max_year;
                        }
                    },

                    async loadCalendar() {
                        const token = ++this.requestToken;
                        this.loading = true;

                        try {
                            const params = new URLSearchParams({
                                year: String(this.year),
                                month: String(this.month),
                            });

                            if (this.employeeId) {
                                params.set('employee_id', String(this.employeeId));
                            }

                            if (this.view === 'team') {
                                params.set('team', '1');
                            }

                            const response = await fetch(`${this.apiUrl}?${params.toString()}`, {
                                headers: {
                                    Accept: 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });

                            if (!response.ok) {
                                throw new Error('Calendar request failed');
                            }

                            const payload = await response.json();
                            if (token !== this.requestToken) {
                                return;
                            }

                            this.calendar = payload.data;
                            this.mode = this.view === 'team' ? 'team' : 'employee';
                            this.syncYears();
                            this.updateBrowserUrl();
                        } catch (error) {
                            console.error(error);
                        } finally {
                            if (token === this.requestToken) {
                                this.loading = false;
                            }
                        }
                    },

                    updateBrowserUrl() {
                        const url = new URL(window.location.href);
                        url.searchParams.set('year', String(this.year));
                        url.searchParams.set('month', String(this.month));
                        if (this.employeeId) {
                            url.searchParams.set('employee_id', String(this.employeeId));
                        } else {
                            url.searchParams.delete('employee_id');
                        }
                        if (this.view && this.view !== 'my') {
                            url.searchParams.set('view', this.view);
                        } else {
                            url.searchParams.delete('view');
                        }
                        window.history.replaceState({}, '', url.toString());
                    },

                    previousMonth() {
                        if (!this.canGoPrevious) {
                            return;
                        }
                        if (this.month === 1) {
                            this.year -= 1;
                            this.month = 12;
                        } else {
                            this.month -= 1;
                        }
                        this.loadCalendar();
                    },

                    nextMonth() {
                        if (!this.canGoNext) {
                            return;
                        }
                        if (this.month === 12) {
                            this.year += 1;
                            this.month = 1;
                        } else {
                            this.month += 1;
                        }
                        this.loadCalendar();
                    },

                    goToday() {
                        this.year = this.todayYear;
                        this.month = this.todayMonth;
                        this.loadCalendar();
                    },

                    onFilterChange() {
                        this.loadCalendar();
                    },

                    viewMember(id) {
                        this.view = 'my';
                        this.employeeId = id;
                        this.loadCalendar();
                    },

                    openDay(day) {
                        this.selectedDay = day;
                        window.dispatchEvent(new CustomEvent('open-drawer-attendance-day'));
                    },

                    formatDayTitle(date) {
                        return new Date(`${date}T00:00:00`).toLocaleDateString(undefined, {
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric',
                        });
                    },

                    cellClasses(day) {
                        const colors = {
                            emerald: 'bg-emerald-50 border-emerald-200 hover:bg-emerald-100',
                            red: 'bg-red-50 border-red-200 hover:bg-red-100',
                            blue: 'bg-blue-50 border-blue-200 hover:bg-blue-100',
                            purple: 'bg-purple-50 border-purple-200 hover:bg-purple-100',
                            orange: 'bg-orange-50 border-orange-200 hover:bg-orange-100',
                            slate: 'bg-slate-100 border-slate-200 hover:bg-slate-200/70',
                            amber: 'bg-amber-50 border-amber-200 hover:bg-amber-100',
                            cyan: 'bg-cyan-50 border-cyan-200 hover:bg-cyan-100',
                            yellow: 'bg-yellow-50 border-yellow-200 hover:bg-yellow-100',
                            neutral: 'bg-neutral-100 border-neutral-300 hover:bg-neutral-200',
                            default: 'bg-surface-card border-line hover:bg-surface-muted',
                        };
                        const borders = {
                            red: 'ring-2 ring-red-400',
                            slate: 'ring-2 ring-slate-300',
                        };
                        const visual = day.visual || {};
                        const classes = [colors[visual.color] || colors.default];
                        if (visual.border) {
                            classes.push(borders[visual.border] || '');
                        }
                        if (day.is_today) {
                            classes.push('ring-2 ring-primary-400 ring-offset-1');
                        }

                        return classes.join(' ');
                    },

                    legendSwatch(color) {
                        const swatches = {
                            emerald: 'bg-emerald-50 border-emerald-200',
                            red: 'bg-red-50 border-red-200',
                            blue: 'bg-blue-50 border-blue-200',
                            purple: 'bg-purple-50 border-purple-200',
                            orange: 'bg-orange-50 border-orange-200',
                            slate: 'bg-slate-100 border-slate-200',
                            amber: 'bg-amber-50 border-amber-200',
                            cyan: 'bg-cyan-50 border-cyan-200',
                            yellow: 'bg-yellow-50 border-yellow-200',
                            neutral: 'bg-neutral-100 border-neutral-300',
                            default: 'bg-surface-muted border-line',
                        };

                        return swatches[color] || swatches.default;
                    },

                    dotColor(color) {
                        const map = {
                            emerald: 'bg-emerald-400',
                            red: 'bg-red-400',
                            blue: 'bg-blue-400',
                            purple: 'bg-purple-400',
                            orange: 'bg-orange-400',
                            slate: 'bg-slate-300',
                            amber: 'bg-amber-400',
                            yellow: 'bg-amber-400',
                            cyan: 'bg-cyan-400',
                            default: 'bg-surface-muted',
                        };

                        return map[color] || map.default;
                    },
                }));
            });
        </script>
    @endpush
@endonce
