<x-app-layout>
    <x-flash-messages />

    <x-layouts.create :title="__('New ticket')" :subtitle="$customer->display_name" max-width="3xl">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('customers'), 'href' => route('customers.index')],
                ['label' => $customer->display_name, 'href' => route('customers.show', $customer)],
                ['label' => __('New ticket'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('customers.tickets.store', $customer) }}">
            @csrf
            @include('tickets._form', ['ticket' => $ticket, 'customer' => $customer, 'assignees' => $assignees])
            <x-forms.footer :cancel-href="route('customers.show', $customer)" :submit-label="__('Create ticket')" />
        </form>
    </x-layouts.create>
</x-app-layout>
