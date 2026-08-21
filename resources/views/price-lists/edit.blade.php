<x-app-layout>
    <x-flash-messages />
    <x-layouts.edit :title="__('Edit :name', ['name' => $priceList->name])" max-width="5xl">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => crm_term('price_lists'), 'href' => route('price-lists.index')],
                ['label' => $priceList->name, 'href' => route('price-lists.show', $priceList)],
                ['label' => __('Edit'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>
        <form method="POST" action="{{ route('price-lists.update', $priceList) }}">
            @csrf
            @method('PUT')
            @include('price-lists._form')
            <x-forms.footer :cancel-href="route('price-lists.show', $priceList)" :submit-label="__('Save price list')" />
        </form>
    </x-layouts.edit>
</x-app-layout>
