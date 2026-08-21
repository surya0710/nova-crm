<x-app-layout>
    <x-flash-messages />

    <x-layouts.edit :title="__('Edit ticket')" :subtitle="$ticket->number" max-width="3xl">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => $customer->display_name, 'href' => route('customers.show', $customer)],
                ['label' => $ticket->number, 'href' => route('tickets.show', $ticket)],
                ['label' => __('Edit'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('tickets.update', $ticket) }}">
            @csrf
            @method('PATCH')
            @include('tickets._form', ['ticket' => $ticket, 'customer' => $customer, 'assignees' => $assignees])
            <x-forms.footer :cancel-href="route('tickets.show', $ticket)" :submit-label="__('Save changes')" />
        </form>
    </x-layouts.edit>
</x-app-layout>
