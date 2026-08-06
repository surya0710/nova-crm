<x-app-layout>
    <x-flash-messages />

    <x-layouts.create
        :title="__('Add Customer')"
        :subtitle="__('Create a new customer account')"
        max-width="4xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('customers'), 'href' => route('customers.index')],
                ['label' => __('Add Customer'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('customers.store') }}">
            @csrf
            @include('customers._form', ['customer' => $customer, 'assignees' => $assignees])
            <x-forms.footer :cancel-href="route('customers.index')" :submit-label="__('Create Customer')" />
        </form>
    </x-layouts.create>
</x-app-layout>
