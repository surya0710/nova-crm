<x-app-layout>
    <x-flash-messages />

    <x-layouts.edit
        :title="__('Edit contact')"
        :subtitle="$contact->name"
        max-width="3xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => __('Contacts'), 'href' => route('contacts.index')],
                ['label' => $contact->name, 'href' => route('contacts.show', $contact)],
                ['label' => __('Edit'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('contacts.update', $contact) }}">
            @csrf
            @method('PATCH')
            @include('contacts._form', ['contact' => $contact])
            <x-forms.footer :cancel-href="route('contacts.show', $contact)" :submit-label="__('Save changes')" />
        </form>
    </x-layouts.edit>
</x-app-layout>
