<x-app-layout>
    <x-flash-messages />
    <x-layouts.edit :title="__('Edit :number', ['number' => $note->number])" max-width="6xl">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => $note->type_label, 'href' => route($note->routePrefix().'.index')],
                ['label' => $note->number, 'href' => route($note->routePrefix().'.show', $note)],
                ['label' => __('Edit'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>
        <form method="POST" action="{{ route($note->routePrefix().'.update', $note) }}">
            @csrf
            @method('PUT')
            @include('adjustment-notes._form')
            <x-forms.footer :cancel-href="route($note->routePrefix().'.show', $note)" :submit-label="__('Save note')" />
        </form>
    </x-layouts.edit>
</x-app-layout>
