<x-app-layout>
    <x-flash-messages />

    <x-layouts.edit
        :title="__('Edit Deal')"
        :subtitle="$opportunity->title"
        max-width="4xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('pipeline'), 'href' => route('pipeline.index')],
                ['label' => $opportunity->title, 'href' => route('pipeline.show', $opportunity)],
                ['label' => __('Edit'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('pipeline.update', $opportunity) }}">
            @csrf
            @method('PATCH')
            @include('pipeline._form', [
                'opportunity' => $opportunity,
                'customers' => $customers,
                'leads' => $leads,
                'assignees' => $assignees,
            ])
            <x-forms.footer :cancel-href="route('pipeline.show', $opportunity)" :submit-label="__('Save Changes')" />
        </form>
    </x-layouts.edit>
</x-app-layout>
