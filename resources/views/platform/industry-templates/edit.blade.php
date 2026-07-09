<x-platform-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-lg font-semibold">{{ __('Edit Draft: :name', ['name' => $template->name]) }}</h1>
            <a href="{{ route('platform.industry-templates.show', $template) }}" class="text-sm text-slate-400 hover:text-white">{{ __('Back to template') }}</a>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('platform.industry-templates.update', $template) }}">
        @csrf
        @method('PATCH')
        @include('platform.industry-templates._form')
    </form>
</x-platform-layout>
