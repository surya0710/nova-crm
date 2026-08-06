{{-- Legacy alias → ui.button secondary --}}
<x-ui.button variant="secondary" {{ $attributes->merge(['type' => $attributes->get('type', 'button')]) }}>
    {{ $slot }}
</x-ui.button>
