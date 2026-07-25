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
                <x-ui.stat-card :label="__('Present Today')" :value="$teamPresentToday" />
                <x-ui.stat-card :label="__('On Leave')" :value="$onLeaveToday->count()" />
                <x-ui.stat-card :label="__('Pending Leave')" :value="$pendingLeave->count()" />
            </x-slot:kpis>

            <div class="space-y-6">
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
