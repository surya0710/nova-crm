<x-app-layout>
    <x-flash-messages />
    <x-layouts.create :title="__('New price list')" max-width="5xl">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('price_lists'), 'href' => route('price-lists.index')],
                ['label' => __('New'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>
        <form method="POST" action="{{ route('price-lists.store') }}">
            @csrf
            @include('price-lists._form')
            <x-forms.footer :cancel-href="route('price-lists.index')" :submit-label="__('Create price list')" />
        </form>
    </x-layouts.create>
</x-app-layout>
