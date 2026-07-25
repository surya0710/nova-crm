<x-app-layout>
    <x-flash-messages />

    <x-layouts.create
        :title="__('Add Deal')"
        :subtitle="__('Create a new pipeline opportunity')"
        max-width="4xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('pipeline'), 'href' => route('pipeline.index')],
                ['label' => __('Add Deal'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('pipeline.store') }}">
            @csrf
            @include('pipeline._form', [
                'opportunity' => $opportunity,
                'customers' => $customers,
                'leads' => $leads,
                'assignees' => $assignees,
            ])
            <x-forms.footer :cancel-href="route('pipeline.index')" :submit-label="__('Create Deal')" />
        </form>
    </x-layouts.create>
</x-app-layout>
