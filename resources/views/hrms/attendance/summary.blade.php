<x-app-layout>
    <x-layouts.entity-listing
        :title="__('Daily Attendance Summary')"
        :subtitle="__('Organization-wide attendance breakdown for a selected date')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Attendance'), 'href' => route('hrms.attendance.index')],
                ['label' => __('Daily Summary'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.attendance.index')" variant="secondary" size="sm">{{ __('Back') }}</x-ui.button>
        </x-slot:actions>

        <x-slot:filters>
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <x-forms.field :label="__('Date')" name="date" class="mb-0">
                    <x-forms.input type="date" name="date" :value="$filterDate" />
                </x-forms.field>
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Load') }}</x-ui.button>
            </form>
        </x-slot:filters>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            @foreach (['total_employees' => __('Total Employees'), 'present' => __('Present'), 'absent' => __('Absent'), 'late' => __('Late'), 'on_leave' => __('On Leave'), 'holiday' => __('Holiday'), 'weekend' => __('Weekend'), 'half_day' => __('Half Day'), 'pending' => __('Pending'), 'overtime' => __('Overtime')] as $key => $label)
                <x-ui.stat-card :label="$label" :value="$summary[$key] ?? 0" />
            @endforeach
        </div>
    </x-layouts.entity-listing>
</x-app-layout>
