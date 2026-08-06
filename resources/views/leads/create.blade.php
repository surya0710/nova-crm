<x-app-layout>
    <x-flash-messages />

    <x-layouts.create
        :title="__('Add Lead')"
        :subtitle="__('Create a new sales lead')"
        max-width="4xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('leads'), 'href' => route('leads.index')],
                ['label' => __('Add Lead'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('leads.store') }}">
            @csrf
            @include('leads._form', ['lead' => $lead, 'assignees' => $assignees])
            <x-forms.footer :cancel-href="route('leads.index')" :submit-label="__('Create Lead')" />
        </form>
    </x-layouts.create>
</x-app-layout>
