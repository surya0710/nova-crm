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
                ['label' => __('attendance.label'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('ess.attendance.records')" variant="secondary" size="sm">{{ __('History') }}</x-ui.button>
            <x-ui.button :href="route('ess.leave.index')" variant="secondary" size="sm">{{ __('Apply Leave') }}</x-ui.button>
        </x-slot:actions>

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

        <x-attendance.calendar-app
            :calendar="$calendar"
            :year="$year"
            :month="$month"
            mode="employee"
            view="my"
            :employee-id="$employee->id"
            :can-view-team="false"
            :can-filter-employees="false"
            :navigation="$navigation ?? []"
            :api-url="$apiUrl ?? url('/api/v1/attendance/calendar')"
        />
    </x-layouts.entity-listing>
</x-app-layout>
