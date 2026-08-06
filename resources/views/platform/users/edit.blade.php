<x-platform-layout>
    <x-layouts.edit
        :title="__('Edit Platform User')"
        :subtitle="$user->name"
        max-width="3xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Platform Users'), 'href' => route('platform.users.index')],
                ['label' => $user->name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('platform.users.update', $user) }}">
            @csrf
            @method('PATCH')
            @include('platform.users._form', ['user' => $user])
            <x-forms.footer :cancel-href="route('platform.users.index')" :submit-label="__('Save Changes')" />
        </form>
    </x-layouts.edit>
</x-platform-layout>
