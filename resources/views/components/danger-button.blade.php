{{-- Legacy alias → ui.button danger --}}
<x-ui.button variant="danger" {{ $attributes->merge(['type' => $attributes->get('type', 'button')]) }}>
    {{ $slot }}
</x-ui.button>
