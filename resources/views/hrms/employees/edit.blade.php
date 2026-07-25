<x-app-layout>
    <x-flash-messages />

    <x-layouts.edit
        :title="__('Edit Employee')"
        :subtitle="$employee->employee_code"
        max-width="4xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Employees'), 'href' => route('hrms.employees.index')],
                ['label' => $employee->full_name, 'href' => route('hrms.employees.show', $employee)],
                ['label' => __('Edit'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('hrms.employees.update', $employee) }}">
            @csrf
            @method('PUT')
            @include('hrms.employees.partials.form')
            <x-forms.footer :cancel-href="route('hrms.employees.show', $employee)" :submit-label="__('Save Changes')" />
        </form>
    </x-layouts.edit>
</x-app-layout>
