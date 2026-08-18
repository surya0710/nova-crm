<x-app-layout>
    <x-flash-messages />

    <x-layouts.edit :title="__('Edit Category')" :subtitle="$category->name" max-width="3xl">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('products'), 'href' => route('products.index')],
                ['label' => __('Categories'), 'href' => route('product-categories.index')],
                ['label' => __('Edit'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('product-categories.update', $category) }}">
            @csrf
            @method('PATCH')
            @include('product-categories._form', ['category' => $category])
            <x-forms.footer :cancel-href="route('product-categories.index')" :submit-label="__('Save Changes')" />
        </form>
    </x-layouts.edit>
</x-app-layout>
