<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">{{ __('Create Role') }}</h1>
    </x-slot>

    <x-flash-messages />
    @include('rbac._nav')

    <div class="max-w-2xl rounded-xl bg-white border border-slate-200 shadow-sm p-6">
        <form method="POST" action="{{ route('rbac.roles.store') }}" class="space-y-4">
            @csrf
            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="slug" :value="__('Slug')" />
                <x-text-input id="slug" name="slug" class="mt-1 block w-full" :value="old('slug')" />
            </div>
            <div>
                <x-input-label for="description" :value="__('Description')" />
                <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description') }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="color" :value="__('Color')" />
                    <x-text-input id="color" name="color" type="color" class="mt-1 block w-full h-10" :value="old('color', '#6366f1')" />
                </div>
                <div>
                    <x-input-label for="hierarchy_level" :value="__('Hierarchy Level')" />
                    <x-text-input id="hierarchy_level" name="hierarchy_level" type="number" min="0" max="100" class="mt-1 block w-full" :value="old('hierarchy_level', 10)" />
                </div>
            </div>
            <div class="flex gap-3">
                <x-primary-button>{{ __('Create Role') }}</x-primary-button>
                <a href="{{ route('rbac.roles.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
