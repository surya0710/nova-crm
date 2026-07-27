<x-app-layout>
    <x-flash-messages />

    <x-layouts.workspace-home
        :title="__('Manager Dashboard')"
        :subtitle="__('Team attendance, leave, and people insights')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Manager Dashboard'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @if (($teamCount ?? 0) === 0)
            <x-ui.card class="max-w-2xl mx-auto text-center px-8 py-12">
                <h2 class="text-lg font-semibold text-ink-heading">{{ __('No employees assigned.') }}</h2>
                <p class="mt-2 text-sm text-ink-muted">{{ __('Once employees report to you, team insights will appear here.') }}</p>
            </x-ui.card>
        @else
            <x-slot:kpis>
                <x-ui.stat-card :label="__('Team Size')" :value="$teamCount" />
                <x-ui.stat-card :label="__('Present')" :value="$teamSummary['present'] ?? 0" />
                <x-ui.stat-card :label="__('Working')" :value="$teamSummary['working'] ?? 0" />
                <x-ui.stat-card :label="__('Late')" :value="$teamSummary['late'] ?? 0" />
                <x-ui.stat-card :label="__('Leave')" :value="$teamSummary['leave'] ?? 0" />
                <x-ui.stat-card :label="__('Absent')" :value="$teamSummary['absent'] ?? 0" />
                <x-ui.stat-card :label="__('Checked Out')" :value="$teamSummary['checked_out'] ?? 0" />
            </x-slot:kpis>

            <div class="space-y-6">
                <x-ui.card>
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <h2 class="text-base font-semibold text-ink-heading">{{ __('Today\'s Attendance') }}</h2>
                        <a href="{{ $teamSummary['attendance_url'] ?? route('hrms.attendance.index') }}" class="text-sm text-primary-700 hover:underline">
                            {{ __('View Attendance') }}
                        </a>
                    </div>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                        <div><p class="text-xs text-ink-muted">{{ __('Present') }}</p><p class="text-2xl font-semibold text-ink-heading">{{ $teamSummary['present'] ?? 0 }}</p></div>
                        <div><p class="text-xs text-ink-muted">{{ __('Late') }}</p><p class="text-2xl font-semibold text-orange-700">{{ $teamSummary['late'] ?? 0 }}</p></div>
                        <div><p class="text-xs text-ink-muted">{{ __('Leave') }}</p><p class="text-2xl font-semibold text-amber-700">{{ $teamSummary['leave'] ?? 0 }}</p></div>
                        <div><p class="text-xs text-ink-muted">{{ __('Absent') }}</p><p class="text-2xl font-semibold text-red-700">{{ $teamSummary['absent'] ?? 0 }}</p></div>
                        <div><p class="text-xs text-ink-muted">{{ __('Working') }}</p><p class="text-2xl font-semibold text-emerald-700">{{ $teamSummary['working'] ?? 0 }}</p></div>
                        <div><p class="text-xs text-ink-muted">{{ __('Checked Out') }}</p><p class="text-2xl font-semibold text-ink-heading">{{ $teamSummary['checked_out'] ?? 0 }}</p></div>
                    </div>
                </x-ui.card>

                <div class="grid gap-6 lg:grid-cols-2">
                    <x-workspace.widget :title="__('Late Today')">
                        @forelse ($teamSummary['late_employees'] ?? [] as $late)
                            <div class="py-2 text-sm border-b border-line last:border-0 text-ink-heading">
                                {{ $late['name'] }}
                                @if (!empty($late['late_minutes']))
                                    <span class="text-ink-muted"> · {{ $late['late_minutes'] }} {{ __('min late') }}</span>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-ink-muted py-4 text-center">{{ __('No late arrivals today.') }}</p>
                        @endforelse
                    </x-workspace.widget>

                    <x-workspace.widget :title="__('On Leave Today')">
                        @forelse ($onLeaveToday as $leave)
                            <div class="py-2 text-sm border-b border-line last:border-0 text-ink-heading">
                                {{ $leave->employee->full_name }} · {{ $leave->leaveType->name }}
                            </div>
                        @empty
                            <p class="text-sm text-ink-muted py-4 text-center">{{ __('No one on leave today.') }}</p>
                        @endforelse
                    </x-workspace.widget>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <x-workspace.widget :title="__('Pending Leave Approvals')">
                        @forelse ($pendingLeave as $leave)
                            <a href="{{ route('hrms.leave-applications.show', $leave) }}" class="block py-2 text-sm border-b border-line last:border-0">
                                <span class="font-medium text-ink-heading hover:text-primary-700">{{ $leave->employee->full_name }}</span>
                                <span class="text-ink-muted"> · {{ $leave->days }} {{ __('days') }}</span>
                            </a>
                        @empty
                            <x-ui.empty-state-preset variant="leave" class="!py-6" />
                        @endforelse
                    </x-workspace.widget>

                    <x-workspace.widget :title="__('Pending Attendance Corrections')">
                        @forelse ($pendingCorrections as $correction)
                            <div class="py-2 text-sm border-b border-line last:border-0 text-ink-heading">
                                {{ $correction->employee->full_name }} · {{ $correction->attendanceRecord->attendance_date->format('M j') }}
                            </div>
                        @empty
                            <x-ui.empty-state-preset variant="attendance" class="!py-6" />
                        @endforelse
                    </x-workspace.widget>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <x-workspace.widget :title="__('Team Birthdays This Month')">
                        @forelse ($birthdays as $member)
                            <div class="py-2 text-sm border-b border-line last:border-0 text-ink-heading">
                                {{ $member->full_name }} · {{ $member->date_of_birth?->format('M j') }}
                            </div>
                        @empty
                            <p class="text-sm text-ink-muted py-4 text-center">{{ __('No birthdays this month.') }}</p>
                        @endforelse
                    </x-workspace.widget>

                    <x-workspace.widget :title="__('Announcements')">
                        @forelse ($announcements as $announcement)
                            <div class="py-2 border-b border-line last:border-0">
                                <p class="font-medium text-sm text-ink-heading">{{ $announcement->title }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-ink-muted py-4 text-center">{{ __('No announcements.') }}</p>
                        @endforelse
                    </x-workspace.widget>
                </div>
            </div>
        @endif
    </x-layouts.workspace-home>
</x-app-layout>
