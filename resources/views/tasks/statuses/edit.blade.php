<x-app-layout>
    <x-flash-messages />

    <x-layouts.edit :title="__('Edit Statuses')" max-width="4xl">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Edit Statuses'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

    <form method="POST" action="{{ route('task-statuses.update', $status) }}" class="max-w-xl rounded-xl bg-white border border-slate-200 shadow-sm p-6 space-y-4">
        @csrf
        @method('PUT')
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" class="mt-1 w-full" :value="old('name', $status->name)" required />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="color" :value="__('Color')" />
            <x-text-input id="color" name="color" class="mt-1 w-full" :value="old('color', $status->color)" />
        </div>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" @checked(old('is_default', $status->is_default))> {{ __('Default') }}</label>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_closed" value="1" @checked(old('is_closed', $status->is_closed))> {{ __('Closed') }}</label>
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </form>
    </x-layouts.edit>
</x-app-layout>
