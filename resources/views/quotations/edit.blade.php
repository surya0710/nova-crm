<x-app-layout>
    <x-flash-messages />

    <x-layouts.edit
        :title="__('Edit Quotation')"
        :subtitle="$quotation->number"
        max-width="6xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('quotations'), 'href' => route('quotations.index')],
                ['label' => $quotation->number, 'href' => route('quotations.show', $quotation)],
                ['label' => __('Edit'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('quotations.update', $quotation) }}">
            @csrf
            @method('PUT')
            @include('quotations._form', ['quotation' => $quotation, 'customers' => $customers, 'opportunities' => $opportunities, 'products' => $products])
            <x-forms.footer :cancel-href="route('quotations.show', $quotation)" :submit-label="__('Save Quotation')" />
        </form>
    </x-layouts.edit>
</x-app-layout>
