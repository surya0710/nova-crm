<x-app-layout>
    <x-flash-messages />

    <x-layouts.edit
        :title="__('Edit :label', ['label' => crm_term('invoice')])"
        :subtitle="$invoice->number"
        max-width="6xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('invoices'), 'href' => route('invoices.index')],
                ['label' => $invoice->number, 'href' => route('invoices.show', $invoice)],
                ['label' => __('Edit'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('invoices.update', $invoice) }}">
            @csrf
            @method('PUT')
            @include('invoices._form', ['invoice' => $invoice, 'customers' => $customers, 'opportunities' => $opportunities, 'products' => $products, 'sourceQuotation' => null])
            <x-forms.footer :cancel-href="route('invoices.show', $invoice)" :submit-label="__('Save')" />
        </form>
    </x-layouts.edit>
</x-app-layout>
