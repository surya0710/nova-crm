@php
    $attendanceTotal = max(1,
        (int) ($attendanceSummary['present'] ?? 0)
        + (int) ($attendanceSummary['late'] ?? 0)
        + (int) ($attendanceSummary['absent'] ?? 0)
        + (int) ($attendanceSummary['on_leave'] ?? 0)
        + (int) ($attendanceSummary['half_day'] ?? 0)
    );
    $deptMax = max(1, (int) ($departmentDistribution->max('total') ?? 1));
    $presentCount = (int) ($attendanceSummary['present'] ?? 0) + (int) ($attendanceSummary['late'] ?? 0);
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.workspace-home
        :title="__('HR Dashboard')"
        :subtitle="__('Workforce overview and daily operations')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('HR Dashboard'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:kpis>
            <x-ui.stat-card
                :label="__('Employees')"
                :value="$employeeCount"
                :hint="__(':count active', ['count' => $activeEmployees])"
            />
            <x-ui.stat-card
                :label="__('Present Today')"
                :value="$presentCount"
                :hint="__('Including late arrivals')"
            />
            <x-ui.stat-card
                :label="__('Leave Requests')"
                :value="$leaveStats['pending_approvals'] ?? 0"
                :hint="__('Pending approvals')"
            />
            <x-ui.stat-card
                :label="__('Assets Assigned')"
                :value="$assetStats['assigned'] ?? 0"
                :hint="__(':count active exits', ['count' => $exitStats['active'] ?? 0])"
            />
        </x-slot:kpis>

        <div class="space-y-6">
            <div class="grid gap-6 lg:grid-cols-2">
                <x-workspace.widget :title="__('Attendance Summary')">
                    <div class="space-y-3">
                        @foreach ([
                            ['label' => __('Present'), 'value' => $presentCount, 'color' => 'bg-emerald-500'],
                            ['label' => __('Absent'), 'value' => (int) ($attendanceSummary['absent'] ?? 0), 'color' => 'bg-rose-500'],
                            ['label' => __('On Leave'), 'value' => (int) ($attendanceSummary['on_leave'] ?? 0), 'color' => 'bg-amber-500'],
                            ['label' => __('Half Day'), 'value' => (int) ($attendanceSummary['half_day'] ?? 0), 'color' => 'bg-sky-500'],
                        ] as $row)
                            <div>
                                <div class="flex items-center justify-between text-sm mb-1">
                                    <span class="font-medium text-ink-heading">{{ $row['label'] }}</span>
                                    <span class="text-ink-muted">{{ $row['value'] }}</span>
                                </div>
                                <div class="h-2.5 rounded-full bg-surface-muted overflow-hidden">
                                    <div class="h-full rounded-full {{ $row['color'] }}" style="width: {{ max(2, ($row['value'] / $attendanceTotal) * 100) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-workspace.widget>

                <x-workspace.widget :title="__('Department Distribution')">
                    @if ($departmentDistribution->isEmpty())
                        <p class="text-sm text-ink-muted py-4 text-center">{{ __('No department data yet.') }}</p>
                    @else
                        <div class="space-y-3">
                            @foreach ($departmentDistribution as $dept)
                                <div>
                                    <div class="flex items-center justify-between text-sm mb-1">
                                        <span class="font-medium text-ink-heading">{{ $dept->department_name }}</span>
                                        <span class="text-ink-muted">{{ $dept->total }}</span>
                                    </div>
                                    <div class="h-2.5 rounded-full bg-surface-muted overflow-hidden">
                                        <div class="h-full rounded-full bg-primary-500" style="width: {{ max(4, ((int) $dept->total / $deptMax) * 100) }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-workspace.widget>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <x-workspace.widget :title="__('Pending Leave')">
                    @forelse ($pendingLeaveApprovals as $leave)
                        <a href="{{ route('hrms.leave-applications.show', $leave) }}" class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                            <span class="font-medium text-ink-heading hover:text-primary-700">{{ $leave->employee->full_name }}</span>
                            <span class="text-xs text-ink-muted shrink-0">{{ $leave->leaveType->name }}</span>
                        </a>
                    @empty
                        <x-ui.empty-state-preset variant="leave" class="!py-6" />
                    @endforelse
                </x-workspace.widget>

                <x-workspace.widget :title="__('Expiring Documents')">
                    @forelse ($expiringDocuments as $doc)
                        <div class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                            <span class="text-ink-heading truncate">{{ $doc->employee->full_name }} · {{ $doc->title }}</span>
                            <x-ui.badge variant="warning">{{ $doc->expires_at?->format('M j') }}</x-ui.badge>
                        </div>
                    @empty
                        <x-ui.empty-state-preset variant="documents" class="!py-6" />
                    @endforelse
                </x-workspace.widget>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <x-workspace.widget :title="__('Upcoming Birthdays')">
                    @forelse ($upcomingBirthdays as $employee)
                        <div class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                            <span class="font-medium text-ink-heading truncate">{{ $employee->full_name }}</span>
                            <span class="text-xs text-ink-muted shrink-0">{{ $employee->date_of_birth?->format('M j') }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted py-4 text-center">{{ __('None this month.') }}</p>
                    @endforelse
                </x-workspace.widget>

                <x-workspace.widget :title="__('Work Anniversaries')">
                    @forelse ($workAnniversaries as $employee)
                        <div class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                            <span class="font-medium text-ink-heading truncate">{{ $employee->full_name }}</span>
                            <span class="text-xs text-ink-muted shrink-0">{{ $employee->joining_date?->format('M j') }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted py-4 text-center">{{ __('None this month.') }}</p>
                    @endforelse
                </x-workspace.widget>

                <x-workspace.widget
                    :title="__('Announcements')"
                    :href="route('hrms.announcements.index')"
                >
                    @forelse ($announcements as $announcement)
                        <div class="py-2 border-b border-line last:border-0 text-sm font-medium text-ink-heading">{{ $announcement->title }}</div>
                    @empty
                        <p class="text-sm text-ink-muted py-4 text-center">{{ __('No announcements.') }}</p>
                    @endforelse
                </x-workspace.widget>
            </div>
        </div>
    </x-layouts.workspace-home>
</x-app-layout>
