<x-app-layout>
    <x-flash-messages />

    <x-layouts.edit
        :title="__('Edit Lead')"
        :subtitle="$lead->name"
        max-width="4xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('leads'), 'href' => route('leads.index')],
                ['label' => $lead->name, 'href' => route('leads.show', $lead)],
                ['label' => __('Edit'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('leads.update', $lead) }}">
            @csrf
            @method('PATCH')
            @include('leads._form', ['lead' => $lead, 'assignees' => $assignees])
            <x-forms.footer :cancel-href="route('leads.show', $lead)" :submit-label="__('Save Changes')" />
        </form>
    </x-layouts.edit>
</x-app-layout>
