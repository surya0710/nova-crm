<x-app-layout>
    <x-slot name="header"><h1 class="text-lg font-semibold text-slate-900">{{ __('Create Permission Group') }}</h1></x-slot>
    <x-flash-messages />
    @include('rbac._nav')
    <div class="max-w-2xl rounded-xl bg-white border border-slate-200 shadow-sm p-6">
        <form method="POST" action="{{ route('rbac.permission-groups.store') }}" class="space-y-4">
            @csrf
            <div><x-input-label for="name" :value="__('Name')" /><x-text-input id="name" name="name" class="mt-1 block w-full" required /></div>
            <div><x-input-label for="slug" :value="__('Slug')" /><x-text-input id="slug" name="slug" class="mt-1 block w-full" /></div>
            <div><x-input-label for="description" :value="__('Description')" /><textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea></div>
            <x-primary-button>{{ __('Create Group') }}</x-primary-button>
        </form>
    </div>
</x-app-layout>
