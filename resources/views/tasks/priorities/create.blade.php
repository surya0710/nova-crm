<x-app-layout>
    <x-flash-messages />

    <x-layouts.create :title="__('Create Priorities')" max-width="4xl">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Create Priorities'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

    <form method="POST" action="{{ route('task-priorities.store') }}" class="max-w-xl rounded-xl bg-white border border-slate-200 shadow-sm p-6 space-y-4">
        @csrf
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" class="mt-1 w-full" :value="old('name')" required />
        </div>
        <div>
            <x-input-label for="level" :value="__('Level')" />
            <x-text-input id="level" type="number" name="level" class="mt-1 w-full" :value="old('level', 1)" />
        </div>
        <div>
            <x-input-label for="color" :value="__('Color')" />
            <x-text-input id="color" name="color" class="mt-1 w-full" :value="old('color', '#0ea5e9')" />
        </div>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" @checked(old('is_default'))> {{ __('Default') }}</label>
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </form>
    </x-layouts.create>
</x-app-layout>
