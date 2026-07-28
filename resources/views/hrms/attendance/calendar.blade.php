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
            <x-ui.button :href="route('hrms.attendance.records')" variant="secondary" size="sm">{{ __('Records') }}</x-ui.button>
            <x-ui.button :href="route('hrms.attendance.summary')" variant="secondary" size="sm">{{ __('Daily Summary') }}</x-ui.button>
        </x-slot:actions>

        <x-attendance.calendar-app
            :calendar="$calendar"
            :year="$year"
            :month="$month"
            :mode="$mode ?? 'employee'"
            :view="$view ?? 'my'"
            :employee-id="$employee?->id"
            :employees="$employees ?? collect()"
            :can-view-team="$canViewTeam ?? false"
            :can-filter-employees="$canFilterEmployees ?? false"
            :navigation="$navigation ?? []"
            :api-url="$apiUrl ?? url('/api/v1/attendance/calendar')"
        />
    </x-layouts.entity-listing>
</x-app-layout>
