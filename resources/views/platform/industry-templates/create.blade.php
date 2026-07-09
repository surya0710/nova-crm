<x-platform-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-lg font-semibold">{{ __('New Industry Template') }}</h1>
            <a href="{{ route('platform.industry-templates.index') }}" class="text-sm text-slate-400 hover:text-white">{{ __('Back to templates') }}</a>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('platform.industry-templates.store') }}">
        @csrf
        @include('platform.industry-templates._form')
    </form>
</x-platform-layout>
