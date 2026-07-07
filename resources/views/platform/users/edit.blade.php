<x-platform-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold">{{ __('Edit Platform User') }}</h1>
    </x-slot>

    <form method="POST" action="{{ route('platform.users.update', $user) }}" class="max-w-lg space-y-4">
        @csrf
        @method('PATCH')
        @include('platform.users._form', ['user' => $user])
        <button type="submit" class="rounded-lg bg-violet-600 hover:bg-violet-500 px-4 py-2 text-sm font-medium">{{ __('Save') }}</button>
    </form>
</x-platform-layout>
