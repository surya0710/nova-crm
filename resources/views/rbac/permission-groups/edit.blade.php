<x-app-layout>
    <x-slot name="header"><h1 class="text-lg font-semibold text-slate-900">{{ __('Edit Permission Group') }}</h1></x-slot>
    <x-flash-messages />
    @include('rbac._nav')
    <div class="max-w-2xl rounded-xl bg-white border border-slate-200 shadow-sm p-6">
        <form method="POST" action="{{ route('rbac.permission-groups.update', $group) }}" class="space-y-4">
            @csrf @method('PATCH')
            <div><x-input-label for="name" :value="__('Name')" /><x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $group->name)" required /></div>
            <div><x-input-label for="description" :value="__('Description')" /><textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description', $group->description) }}</textarea></div>
            <x-primary-button>{{ __('Save') }}</x-primary-button>
        </form>
    </div>
</x-app-layout>
