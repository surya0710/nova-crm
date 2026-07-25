<x-app-layout>
    <x-flash-messages />

    <x-layouts.workspace-home
        :title="__('HR')"
        :subtitle="__('Hire, support, and pay people')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-workspace.quick-actions :actions="$quickActions" />
        </x-slot:actions>

        <x-slot:kpis>
            @forelse ($kpis as $kpi)
                <x-ui.stat-card
                    :label="$kpi['label']"
                    :value="$kpi['value']"
                    :hint="$kpi['hint'] ?? null"
                />
            @empty
                <x-ui.stat-card :label="__('HR')" :value="__('—')" :hint="__('No metrics available for your role')" />
            @endforelse
        </x-slot:kpis>

        <div class="space-y-6">
            @if ($employeeSummary)
                <x-workspace.widget
                    :title="__('Employee summary')"
                    :subtitle="__('Headcount pulse')"
                    :href="$employeeSummary['href']"
                >
                    <dl class="grid grid-cols-1 gap-3 sm:grid-cols-3 text-sm">
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Active') }}</dt>
                            <dd class="mt-1 font-semibold text-ink-heading">{{ $employeeSummary['active'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Total') }}</dt>
                            <dd class="mt-1 font-semibold text-ink-heading">{{ $employeeSummary['total'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('New joiners') }}</dt>
                            <dd class="mt-1 font-semibold text-ink-heading">{{ $employeeSummary['new_joiners'] }}</dd>
                        </div>
                    </dl>
                </x-workspace.widget>
            @endif

            <div class="grid gap-6 md:grid-cols-2">
                @if ($attendanceToday)
                    <x-workspace.widget
                        :title="__('Attendance today')"
                        :href="$attendanceToday['href'] ?? null"
                    >
                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-xs text-ink-muted">{{ __('Present') }}</dt>
                                <dd class="mt-1 font-semibold text-ink-heading">{{ $attendanceToday['present'] ?? 0 }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-ink-muted">{{ __('Absent') }}</dt>
                                <dd class="mt-1 font-semibold text-ink-heading">{{ $attendanceToday['absent'] ?? 0 }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-ink-muted">{{ __('Late') }}</dt>
                                <dd class="mt-1 font-semibold text-ink-heading">{{ $attendanceToday['late'] ?? 0 }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-ink-muted">{{ __('On leave') }}</dt>
                                <dd class="mt-1 font-semibold text-ink-heading">{{ $attendanceToday['on_leave'] ?? 0 }}</dd>
                            </div>
                        </dl>
                    </x-workspace.widget>
                @endif

                @if ($attendancePercentage)
                    <x-workspace.widget
                        :title="__('Attendance percentage')"
                        :href="$attendancePercentage['href'] ?? null"
                    >
                        <div class="flex items-end justify-between gap-4">
                            <p class="text-3xl font-semibold text-ink-heading">{{ $attendancePercentage['percent'] }}%</p>
                            <p class="text-sm text-ink-muted">
                                {{ __(':present of :expected expected', [
                                    'present' => $attendancePercentage['present'],
                                    'expected' => $attendancePercentage['expected'],
                                ]) }}
                            </p>
                        </div>
                    </x-workspace.widget>
                @endif
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <x-workspace.widget
                    :title="__('Employees on leave')"
                    :href="auth()->user()->hasPermission('leave.view') && \Illuminate\Support\Facades\Route::has('hrms.leave.dashboard') ? route('hrms.leave.dashboard') : null"
                >
                    @forelse ($employeesOnLeave as $leave)
                        <a href="{{ \Illuminate\Support\Facades\Route::has('hrms.leave-applications.show') ? route('hrms.leave-applications.show', $leave) : '#' }}" class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                            <div class="min-w-0">
                                <p class="font-medium text-ink-heading truncate hover:text-primary-700">{{ $leave->employee?->full_name }}</p>
                                <p class="text-xs text-ink-muted truncate">{{ $leave->leaveType?->name }}</p>
                            </div>
                            <span class="text-xs text-ink-muted shrink-0">{{ $leave->end_date?->format('M j') }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-ink-muted py-4 text-center">{{ __('Nobody is on leave today.') }}</p>
                    @endforelse
                </x-workspace.widget>

                <x-workspace.widget
                    :title="__('Pending leave requests')"
                    :href="auth()->user()->hasPermission('leave.view') && \Illuminate\Support\Facades\Route::has('hrms.leave-applications.approval-queue') ? route('hrms.leave-applications.approval-queue') : (auth()->user()->hasPermission('leave.view') && \Illuminate\Support\Facades\Route::has('hrms.leave.dashboard') ? route('hrms.leave.dashboard') : null)"
                >
                    @forelse ($pendingLeaveRequests as $leave)
                        <a href="{{ \Illuminate\Support\Facades\Route::has('hrms.leave-applications.show') ? route('hrms.leave-applications.show', $leave) : '#' }}" class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                            <div class="min-w-0">
                                <p class="font-medium text-ink-heading truncate hover:text-primary-700">{{ $leave->employee?->full_name }}</p>
                                <p class="text-xs text-ink-muted truncate">{{ $leave->leaveType?->name }} · {{ $leave->start_date?->format('M j') }} – {{ $leave->end_date?->format('M j') }}</p>
                            </div>
                            <x-ui.badge variant="warning">{{ __('Pending') }}</x-ui.badge>
                        </a>
                    @empty
                        <x-ui.empty-state-preset variant="leave" class="!py-6" />
                    @endforelse
                </x-workspace.widget>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <x-workspace.widget :title="__('Upcoming birthdays')">
                    @forelse ($upcomingBirthdays as $employee)
                        <div class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                            <a href="{{ route('hrms.employees.show', $employee) }}" class="font-medium text-ink-heading truncate hover:text-primary-700">{{ $employee->full_name }}</a>
                            <span class="text-xs text-ink-muted shrink-0">{{ $employee->date_of_birth?->format('M j') }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted py-4 text-center">{{ __('No birthdays this month.') }}</p>
                    @endforelse
                </x-workspace.widget>

                <x-workspace.widget :title="__('Work anniversaries')">
                    @forelse ($upcomingAnniversaries as $employee)
                        <div class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                            <a href="{{ route('hrms.employees.show', $employee) }}" class="font-medium text-ink-heading truncate hover:text-primary-700">{{ $employee->full_name }}</a>
                            <span class="text-xs text-ink-muted shrink-0">{{ $employee->joining_date?->format('M j') }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted py-4 text-center">{{ __('No anniversaries this month.') }}</p>
                    @endforelse
                </x-workspace.widget>
            </div>

            <x-workspace.widget
                :title="__('New joiners')"
                :href="auth()->user()->hasPermission('hrms.view') ? route('hrms.employees.index') : null"
            >
                @forelse ($newJoiners as $employee)
                    <a href="{{ route('hrms.employees.show', $employee) }}" class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                        <div class="min-w-0">
                            <p class="font-medium text-ink-heading truncate hover:text-primary-700">{{ $employee->full_name }}</p>
                            <p class="text-xs text-ink-muted truncate">{{ $employee->department?->name ?? '—' }}</p>
                        </div>
                        <span class="text-xs text-ink-muted shrink-0">{{ $employee->joining_date?->format('M j') }}</span>
                    </a>
                @empty
                    <x-ui.empty-state-preset variant="employees" class="!py-6" />
                @endforelse
            </x-workspace.widget>

            @if ($recruitmentPipeline)
                <x-workspace.widget
                    :title="__('Recruitment pipeline')"
                    :href="$recruitmentPipeline['href']"
                >
                    @if (empty($recruitmentPipeline['stages']))
                        <x-ui.empty-state-preset variant="recruitment" class="!py-6" />
                    @else
                        <dl class="grid grid-cols-2 gap-3 sm:grid-cols-3 text-sm">
                            @foreach ($recruitmentPipeline['stages'] as $stage => $total)
                                <div>
                                    <dt class="text-xs text-ink-muted">{{ config('hrms.recruitment.application_stages.'.$stage, $stage) }}</dt>
                                    <dd class="mt-1 font-semibold text-ink-heading">{{ $total }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </x-workspace.widget>
            @endif

            <div class="grid gap-6 md:grid-cols-2">
                <x-workspace.widget
                    :title="__('Open positions')"
                    :href="auth()->user()->hasPermission('recruitment.view') && \Illuminate\Support\Facades\Route::has('hrms.recruitment.openings.index') ? route('hrms.recruitment.openings.index') : null"
                >
                    @forelse ($openPositions as $opening)
                        <a href="{{ route('hrms.recruitment.openings.show', $opening) }}" class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                            <div class="min-w-0">
                                <p class="font-medium text-ink-heading truncate hover:text-primary-700">{{ $opening->title }}</p>
                                <p class="text-xs text-ink-muted truncate">{{ $opening->department?->name ?? '—' }}</p>
                            </div>
                            <x-ui.badge variant="primary">{{ $opening->status }}</x-ui.badge>
                        </a>
                    @empty
                        <x-ui.empty-state-preset variant="candidates" class="!py-6" />
                    @endforelse
                </x-workspace.widget>

                <x-workspace.widget
                    :title="__('Interview schedule')"
                    :href="auth()->user()->hasPermission('recruitment.view') && \Illuminate\Support\Facades\Route::has('hrms.recruitment.interview-rounds.index') ? route('hrms.recruitment.interview-rounds.index') : null"
                >
                    @forelse ($interviewSchedule as $round)
                        @php
                            $candidate = $round->jobApplication?->candidate;
                            $opening = $round->jobApplication?->jobOpening;
                        @endphp
                        <div class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                            <div class="min-w-0">
                                <p class="font-medium text-ink-heading truncate">{{ $candidate?->fullName() ?? __('Interview') }}</p>
                                <p class="text-xs text-ink-muted truncate">{{ $opening?->title ?? '—' }}</p>
                            </div>
                            <span class="text-xs text-ink-muted shrink-0">{{ $round->scheduled_at?->format('M j, g:i A') }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted py-4 text-center">{{ __('No upcoming interviews.') }}</p>
                    @endforelse
                </x-workspace.widget>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                @if ($performanceOverview)
                    <x-workspace.widget :title="__('Performance snapshot')" :href="$performanceOverview['href']">
                        <dl class="text-sm">
                            <div class="flex justify-between gap-3 py-1">
                                <dt class="text-ink-muted">{{ __('Open reviews') }}</dt>
                                <dd class="font-semibold text-ink-heading">{{ $performanceOverview['open_reviews'] }}</dd>
                            </div>
                        </dl>
                    </x-workspace.widget>
                @endif

                @if ($payrollSummary)
                    <x-workspace.widget :title="__('Payroll summary')" :href="$payrollSummary['href']">
                        <dl class="text-sm">
                            <div class="flex justify-between gap-3 py-1">
                                <dt class="text-ink-muted">{{ __('Latest run') }}</dt>
                                <dd class="font-semibold text-ink-heading">{{ $payrollSummary['status'] }}</dd>
                            </div>
                            @if ($payrollSummary['period'])
                                <div class="flex justify-between gap-3 py-1">
                                    <dt class="text-ink-muted">{{ __('Period') }}</dt>
                                    <dd class="font-semibold text-ink-heading">{{ $payrollSummary['period'] }}</dd>
                                </div>
                            @endif
                        </dl>
                    </x-workspace.widget>
                @endif

                @if ($assetsAssigned)
                    <x-workspace.widget :title="__('Assets assigned')" :href="$assetsAssigned['href']">
                        <dl class="text-sm">
                            <div class="flex justify-between gap-3 py-1">
                                <dt class="text-ink-muted">{{ __('Assigned assets') }}</dt>
                                <dd class="font-semibold text-ink-heading">{{ $assetsAssigned['assigned'] }}</dd>
                            </div>
                        </dl>
                    </x-workspace.widget>
                @endif
            </div>

            @if ($departmentOverview->isNotEmpty())
                <x-workspace.widget
                    :title="__('Department overview')"
                    :href="auth()->user()->hasPermission('hrms.view') && \Illuminate\Support\Facades\Route::has('hrms.departments.index') ? route('hrms.departments.index') : null"
                >
                    <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4 text-sm">
                        @foreach ($departmentOverview as $department)
                            <div>
                                <dt class="text-xs text-ink-muted truncate">{{ $department['name'] }}</dt>
                                <dd class="mt-1 font-semibold text-ink-heading">{{ $department['total'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-workspace.widget>
            @endif

            <x-workspace.widget
                :title="__('Upcoming holidays')"
                :href="\Illuminate\Support\Facades\Route::has('hrms.calendar') ? route('hrms.calendar') : null"
            >
                @forelse ($upcomingHolidays as $holiday)
                    <div class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                        <span class="font-medium text-ink-heading truncate">{{ $holiday->name }}</span>
                        <span class="text-xs text-ink-muted shrink-0">{{ $holiday->holiday_date?->format('M j') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted py-4 text-center">{{ __('No upcoming holidays.') }}</p>
                @endforelse
            </x-workspace.widget>

            <x-workspace.widget :title="__('Recent activities')">
                @forelse ($recentActivities as $item)
                    <a href="{{ $item['href'] ?? '#' }}" class="flex items-start justify-between gap-3 py-2.5 border-b border-line last:border-0">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-ink-heading truncate hover:text-primary-700">{{ $item['title'] }}</p>
                            <p class="text-xs text-ink-muted">{{ $item['subtitle'] }}</p>
                        </div>
                        <span class="text-xs text-ink-muted shrink-0">{{ $item['when'] }}</span>
                    </a>
                @empty
                    <x-ui.empty-state-preset variant="activities" class="!py-6" />
                @endforelse
            </x-workspace.widget>
        </div>

        <x-slot:aside>
            <x-workspace.attention-rail :title="__('Needs attention')">
                @forelse ($attention as $item)
                    <x-workspace.attention-item
                        :href="$item['href'] ?? null"
                        :title="$item['title']"
                        :subtitle="$item['subtitle'] ?? null"
                        :badge="$item['badge'] ?? null"
                    />
                @empty
                @endforelse
            </x-workspace.attention-rail>

            <x-entity.section :title="__('Pinned pages')">
                @forelse ($pinnedPages as $page)
                    <a href="{{ $page['href'] }}" class="block text-sm font-medium text-primary-600 hover:text-primary-700 py-1.5">{{ $page['label'] }}</a>
                @empty
                    <p class="text-sm text-ink-muted">{{ __('Pin pages from the shell favorites control.') }}</p>
                @endforelse
            </x-entity.section>

            @if (auth()->user()->hasPermission('ess.access') && \Illuminate\Support\Facades\Route::has('ess.dashboard'))
                <x-entity.section :title="__('My HR')">
                    <a href="{{ route('ess.dashboard') }}" class="block text-sm font-medium text-primary-600 hover:text-primary-700 py-1.5">{{ __('My Dashboard') }}</a>
                    <a href="{{ route('ess.leave.index') }}" class="block text-sm font-medium text-primary-600 hover:text-primary-700 py-1.5">{{ __('My Leave') }}</a>
                    <a href="{{ route('ess.attendance.index') }}" class="block text-sm font-medium text-primary-600 hover:text-primary-700 py-1.5">{{ __('My Attendance') }}</a>
                    <a href="{{ route('ess.payroll.index') }}" class="block text-sm font-medium text-primary-600 hover:text-primary-700 py-1.5">{{ __('My Payroll') }}</a>
                </x-entity.section>
            @endif
        </x-slot:aside>
    </x-layouts.workspace-home>
</x-app-layout>
