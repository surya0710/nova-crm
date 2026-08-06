<x-app-layout>
    <x-flash-messages />

    <x-layouts.create
        :title="__('Add Employee')"
        :subtitle="__('Create a new employee record')"
        max-width="4xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Employees'), 'href' => route('hrms.employees.index')],
                ['label' => __('Add Employee'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('hrms.employees.store') }}">
            @csrf
            @include('hrms.employees.partials.form')
            <x-forms.footer :cancel-href="route('hrms.employees.index')" :submit-label="__('Create Employee')" />
        </form>
    </x-layouts.create>
</x-app-layout>
