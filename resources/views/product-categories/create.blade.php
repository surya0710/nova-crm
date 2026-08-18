<x-app-layout>
    <x-flash-messages />

    <x-layouts.create :title="__('Add Category')" max-width="3xl">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('products'), 'href' => route('products.index')],
                ['label' => __('Categories'), 'href' => route('product-categories.index')],
                ['label' => __('Add Category'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('product-categories.store') }}">
            @csrf
            @include('product-categories._form', ['category' => $category])
            <x-forms.footer :cancel-href="route('product-categories.index')" :submit-label="__('Create Category')" />
        </form>
    </x-layouts.create>
</x-app-layout>
