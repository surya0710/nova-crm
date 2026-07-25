<x-app-layout>
    <x-flash-messages />

    <x-layouts.edit
        :title="__('Edit Customer')"
        :subtitle="$customer->display_name"
        max-width="4xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('customers'), 'href' => route('customers.index')],
                ['label' => $customer->display_name, 'href' => route('customers.show', $customer)],
                ['label' => __('Edit'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('customers.update', $customer) }}">
            @csrf
            @method('PATCH')
            @include('customers._form', ['customer' => $customer, 'assignees' => $assignees])
            <x-forms.footer :cancel-href="route('customers.show', $customer)" :submit-label="__('Save Changes')" />
        </form>
    </x-layouts.edit>
</x-app-layout>
