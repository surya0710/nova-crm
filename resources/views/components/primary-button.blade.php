{{-- Legacy alias → ui.button primary --}}
<x-ui.button variant="primary" {{ $attributes->merge(['type' => $attributes->get('type', 'submit')]) }}>
    {{ $slot }}
</x-ui.button>
