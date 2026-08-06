<x-app-layout>
    <x-flash-messages />

    <x-layouts.edit
        :title="__('Edit Product')"
        :subtitle="$product->name"
        max-width="4xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('products'), 'href' => route('products.index')],
                ['label' => $product->name, 'href' => route('products.show', $product)],
                ['label' => __('Edit'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('products.update', $product) }}">
            @csrf
            @method('PATCH')
            @include('products._form', ['product' => $product])
            <x-forms.footer :cancel-href="route('products.show', $product)" :submit-label="__('Save Changes')" />
        </form>
    </x-layouts.edit>
</x-app-layout>
