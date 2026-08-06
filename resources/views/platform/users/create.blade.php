<x-platform-layout>
    <x-layouts.create
        :title="__('Create Platform User')"
        :subtitle="__('Add an administrator to the platform console')"
        max-width="3xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Platform Users'), 'href' => route('platform.users.index')],
                ['label' => __('Create User'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('platform.users.store') }}">
            @csrf
            @include('platform.users._form')
            <x-forms.footer :cancel-href="route('platform.users.index')" :submit-label="__('Create User')" />
        </form>
    </x-layouts.create>
</x-platform-layout>
