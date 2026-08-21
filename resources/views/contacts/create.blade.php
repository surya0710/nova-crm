<x-app-layout>
    <x-flash-messages />

    <x-layouts.create
        :title="__('Add contact')"
        :subtitle="$customer->display_name"
        max-width="3xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('customers'), 'href' => route('customers.index')],
                ['label' => $customer->display_name, 'href' => route('customers.show', $customer)],
                ['label' => __('Add contact'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('customers.contacts.store', $customer) }}">
            @csrf
            @include('contacts._form', ['contact' => $contact])
            <x-forms.footer :cancel-href="route('customers.show', $customer)" :submit-label="__('Create contact')" />
        </form>
    </x-layouts.create>
</x-app-layout>
