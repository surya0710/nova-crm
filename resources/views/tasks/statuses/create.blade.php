<x-app-layout>
    <x-flash-messages />

    <x-layouts.create :title="__('Create Statuses')" max-width="4xl">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Create Statuses'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

    <form method="POST" action="{{ route('task-statuses.store') }}" class="max-w-xl rounded-xl bg-white border border-slate-200 shadow-sm p-6 space-y-4">
        @csrf
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" class="mt-1 w-full" :value="old('name')" required />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="color" :value="__('Color')" />
            <x-text-input id="color" name="color" class="mt-1 w-full" :value="old('color', '#94a3b8')" />
        </div>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" @checked(old('is_default'))> {{ __('Default') }}</label>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_closed" value="1" @checked(old('is_closed'))> {{ __('Closed') }}</label>
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </form>
    </x-layouts.create>
</x-app-layout>
