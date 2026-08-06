<x-app-layout>
    <x-flash-messages />

    <x-layouts.create
        :title="__('Add Product')"
        :subtitle="__('Add a product or service to your catalog')"
        max-width="4xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('products'), 'href' => route('products.index')],
                ['label' => __('Add Product'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('products.store') }}">
            @csrf
            @include('products._form', ['product' => $product])
            <x-forms.footer :cancel-href="route('products.index')" :submit-label="__('Create Product')" />
        </form>
    </x-layouts.create>
</x-app-layout>
