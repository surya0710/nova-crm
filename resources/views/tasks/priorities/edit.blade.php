<x-app-layout>
    <x-flash-messages />

    <x-layouts.edit :title="__('Edit Priorities')" max-width="4xl">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Edit Priorities'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

    <form method="POST" action="{{ route('task-priorities.update', $priority) }}" class="max-w-xl rounded-xl bg-white border border-slate-200 shadow-sm p-6 space-y-4">
        @csrf
        @method('PUT')
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" class="mt-1 w-full" :value="old('name', $priority->name)" required />
        </div>
        <div>
            <x-input-label for="level" :value="__('Level')" />
            <x-text-input id="level" type="number" name="level" class="mt-1 w-full" :value="old('level', $priority->level)" />
        </div>
        <div>
            <x-input-label for="color" :value="__('Color')" />
            <x-text-input id="color" name="color" class="mt-1 w-full" :value="old('color', $priority->color)" />
        </div>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" @checked(old('is_default', $priority->is_default))> {{ __('Default') }}</label>
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </form>
    </x-layouts.edit>
</x-app-layout>
