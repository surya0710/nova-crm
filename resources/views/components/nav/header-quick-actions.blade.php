@props(['actions' => []])

@if (count($actions))
    <x-ui.dropdown align="right" width="56">
        <x-slot:trigger>
            <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-surface-muted px-3 py-1.5 text-sm font-medium text-ink-heading hover:bg-app"
                aria-label="{{ __('Quick actions') }}"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
                <span class="hidden sm:inline">{{ __('Quick actions') }}</span>
            </button>
        </x-slot:trigger>
        <x-slot:content>
            @foreach ($actions as $action)
                <a href="{{ $action['href'] }}" class="block px-3 py-2 text-sm text-ink hover:bg-surface-muted">
                    {{ $action['label'] }}
                </a>
            @endforeach
        </x-slot:content>
    </x-ui.dropdown>
@endif
