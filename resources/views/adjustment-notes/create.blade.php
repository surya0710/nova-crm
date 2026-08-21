<x-app-layout>
    <x-flash-messages />
    <x-layouts.create :title="$type === 'debit' ? __('New Debit Note') : __('New Credit Note')" max-width="6xl">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term($type === 'debit' ? 'debit_notes' : 'credit_notes'), 'href' => route($type === 'debit' ? 'debit-notes.index' : 'credit-notes.index')],
                ['label' => __('New'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>
        <form method="POST" action="{{ route($type === 'debit' ? 'debit-notes.store' : 'credit-notes.store') }}">
            @csrf
            @include('adjustment-notes._form')
            <x-forms.footer :cancel-href="route($type === 'debit' ? 'debit-notes.index' : 'credit-notes.index')" :submit-label="__('Create note')" />
        </form>
    </x-layouts.create>
</x-app-layout>
