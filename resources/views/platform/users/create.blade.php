<x-platform-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold">{{ __('Create Platform User') }}</h1>
    </x-slot>

    <form method="POST" action="{{ route('platform.users.store') }}" class="max-w-lg space-y-4">
        @csrf
        @include('platform.users._form')
        <button type="submit" class="rounded-lg bg-violet-600 hover:bg-violet-500 px-4 py-2 text-sm font-medium">{{ __('Create') }}</button>
    </form>
</x-platform-layout>
