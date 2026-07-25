<x-app-layout>
    <x-flash-messages />

    <x-layouts.workspace-home
        :title="__('My HR Dashboard')"
        :subtitle="$employee->full_name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('My HR'), 'href' => route('ess.dashboard')],
                ['label' => __('Dashboard'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @include('ess.partials.nav')

        <div class="space-y-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <x-ui.card class="md:col-span-2">
                    <h2 class="font-medium text-ink-heading mb-2">{{ $employee->full_name }}</h2>
                    <p class="text-sm text-ink-muted">{{ $employee->department?->name ?? '—' }} · {{ $employee->designation?->name ?? '—' }}</p>
                    <p class="text-sm text-ink-muted mt-1">{{ __('Manager') }}: {{ $employee->reportingManager?->full_name ?? '—' }}</p>
                </x-ui.card>
                <x-ui.card>
                    <p class="text-xs text-ink-muted">{{ __('Today') }}</p>
                    @if ($onLeaveToday)
                        <p class="text-lg font-semibold text-amber-700">{{ __('On Leave') }}</p>
                    @elseif ($todayAttendance)
                        <p class="text-lg font-semibold text-ink-heading">{{ ucfirst($todayAttendance->status) }}</p>
                    @else
                        <p class="text-lg font-semibold text-ink-muted">{{ __('Not clocked in') }}</p>
                    @endif
                    @if ($currentShift)
                        <p class="text-xs text-ink-muted mt-1">{{ $currentShift->name }} ({{ $currentShift->start_time }}–{{ $currentShift->end_time }})</p>
                    @endif
                </x-ui.card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <x-workspace.widget :title="__('Leave Balances')">
                    @forelse ($leaveBalances as $balance)
                        <div class="flex justify-between text-sm py-2 border-b border-line last:border-0">
                            <span class="text-ink-heading">{{ $balance->leaveType->name }}</span>
                            <span class="font-medium text-ink-heading">{{ $balance->balance }}</span>
                        </div>
                    @empty
                        <x-ui.empty-state-preset variant="leave" class="!py-6" />
                    @endforelse
                </x-workspace.widget>

                <x-workspace.widget :title="__('Pending Leave')">
                    @forelse ($pendingLeave as $leave)
                        <div class="text-sm py-2 border-b border-line last:border-0 text-ink-heading">
                            {{ $leave->leaveType->name }} · {{ $leave->start_date->format('M j') }}–{{ $leave->end_date->format('M j') }}
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted py-4 text-center">{{ __('No pending leave.') }}</p>
                    @endforelse
                </x-workspace.widget>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <x-workspace.widget
                    :title="__('Recent Documents')"
                    :href="route('ess.documents.index')"
                >
                    @forelse ($recentDocuments as $doc)
                        <a href="{{ route('ess.documents.show', $doc) }}" class="block text-sm text-primary-700 hover:text-primary-800 py-2 border-b border-line last:border-0">{{ $doc->title }}</a>
                    @empty
                        <x-ui.empty-state-preset variant="documents" class="!py-6" />
                    @endforelse
                </x-workspace.widget>

                <x-workspace.widget :title="__('Announcements')">
                    @forelse ($announcements as $announcement)
                        <div class="py-2 border-b border-line last:border-0">
                            <p class="font-medium text-sm text-ink-heading">{{ $announcement->title }}</p>
                            <p class="text-xs text-ink-muted">{{ Str::limit($announcement->body, 100) }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-ink-muted py-4 text-center">{{ __('No announcements.') }}</p>
                    @endforelse
                </x-workspace.widget>
            </div>
        </div>
    </x-layouts.workspace-home>
</x-app-layout>
